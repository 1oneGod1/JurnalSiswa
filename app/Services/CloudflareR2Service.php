<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CloudflareR2Service
{
    public function storeDocumentation(UploadedFile $file, string $groupId, string $journalId): array
    {
        $directory = 'documentations/'.$groupId.'/'.$journalId;
        $filename = now()->format('YmdHis').'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $filename = trim($filename, '-').($extension ? '.'.$extension : '');
        $path = $directory.'/'.$filename;

        if ($workerUrl = $this->workerUrl()) {
            return $this->uploadViaWorker($workerUrl, $file, $path);
        }

        return $this->uploadViaS3($file, $directory, $filename);
    }

    private function uploadViaWorker(string $workerUrl, UploadedFile $file, string $path): array
    {
        $attempts = $this->workerAttempts($workerUrl, $file, $path);
        $errors = [];

        foreach ($attempts as $label => $send) {
            try {
                $response = $send();

                if ($response->successful()) {
                    return [
                        'file_name' => $file->getClientOriginalName(),
                        'stored_name' => basename($path),
                        'path' => $path,
                        'url' => $this->publicUrl($path),
                        'mime_type' => $file->getClientMimeType(),
                        'file_type' => $this->fileType($file),
                        'size' => $file->getSize(),
                        'storage_disk' => 'r2-worker',
                        'worker_strategy' => $label,
                    ];
                }

                $errors[$label] = $response->status().' '.Str::limit($response->body(), 200);
            } catch (Throwable $e) {
                $errors[$label] = $e->getMessage();
            }
        }

        Log::error('Semua strategi upload Worker gagal', ['errors' => $errors, 'worker_url' => $workerUrl]);

        throw new RuntimeException('Upload via Worker gagal: '.json_encode($errors, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, callable(): \Illuminate\Http\Client\Response>
     */
    private function workerAttempts(string $workerUrl, UploadedFile $file, string $path): array
    {
        $base = rtrim($workerUrl, '/');
        $contents = file_get_contents($file->getRealPath());
        $mime = $file->getClientMimeType();

        $multipart = fn (): PendingRequest => Http::asMultipart()
            ->timeout(60)
            ->attach('file', $contents, basename($path));

        $putRaw = fn (): PendingRequest => Http::withBody($contents, $mime)->timeout(60);

        return [
            'PUT /{path} (raw body)' => fn () => $putRaw()->put($base.'/'.ltrim($path, '/')),
            'POST /{path} (multipart)' => fn () => $multipart()->post($base.'/'.ltrim($path, '/'), ['key' => $path]),
            'POST /upload?key={path} (multipart)' => fn () => $multipart()->post($base.'/upload?key='.urlencode($path), ['key' => $path]),
            'POST / (multipart with key)' => fn () => $multipart()->post($base, ['key' => $path]),
            'PUT /upload/{path} (raw)' => fn () => $putRaw()->put($base.'/upload/'.ltrim($path, '/')),
        ];
    }

    private function uploadViaS3(UploadedFile $file, string $directory, string $filename): array
    {
        $disk = $this->disk();
        $path = Storage::disk($disk)->putFileAs($directory, $file, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Upload file gagal disimpan.');
        }

        return array_filter([
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $disk === 'r2' ? $this->publicUrl($path) : Storage::disk($disk)->url($path),
            'mime_type' => $file->getClientMimeType(),
            'file_type' => $this->fileType($file),
            'size' => $file->getSize(),
            'storage_disk' => $disk,
        ], fn ($value) => $value !== null);
    }

    private function publicUrl(string $path): ?string
    {
        $base = rtrim((string) config('filesystems.disks.r2.url', ''), '/');

        return filled($base) ? $base.'/'.ltrim($path, '/') : null;
    }

    private function workerUrl(): ?string
    {
        $url = (string) env('R2_WORKER_URL', '');

        return $url !== '' ? $url : null;
    }

    private function disk(): string
    {
        $r2 = config('filesystems.disks.r2');
        $hasR2Config = filled($r2['key'] ?? null)
            && filled($r2['secret'] ?? null)
            && filled($r2['bucket'] ?? null)
            && filled($r2['endpoint'] ?? null);

        if (! $hasR2Config) {
            return 'public';
        }

        return class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class) ? 'r2' : 'public';
    }

    private function fileType(UploadedFile $file): string
    {
        $mime = (string) $file->getClientMimeType();

        return match (true) {
            str_starts_with($mime, 'image/') => 'foto',
            str_starts_with($mime, 'video/') => 'video',
            str_contains($mime, 'pdf') => 'dokumen',
            default => 'file',
        };
    }
}
