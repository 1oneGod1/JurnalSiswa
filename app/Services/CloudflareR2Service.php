<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

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
        $base = rtrim($workerUrl, '/');
        $endpoint = $base.'/upload';

        $response = Http::timeout(60)
            ->attach('file', file_get_contents($file->getRealPath()), basename($path))
            ->post($endpoint, ['key' => $path]);

        if (! $response->successful()) {
            Log::error('Worker upload gagal', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
                'key' => $path,
            ]);

            throw new RuntimeException('Upload via Worker gagal ('.$response->status().'): '.Str::limit($response->body(), 200));
        }

        $data = $response->json() ?? [];
        $publicUrl = $data['publicUrl'] ?? $this->publicUrl($path);

        return [
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $publicUrl,
            'mime_type' => $file->getClientMimeType(),
            'file_type' => $this->fileType($file),
            'size' => $file->getSize(),
            'storage_disk' => 'r2-worker',
        ];
    }

    private function uploadViaS3(UploadedFile $file, string $directory, string $filename): array
    {
        $disk = $this->disk();
        $path = Storage::disk($disk)->putFileAs($directory, $file, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Upload file gagal disimpan.');
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $this->urlFor($disk, $path),
            'mime_type' => $file->getClientMimeType(),
            'file_type' => $this->fileType($file),
            'size' => $file->getSize(),
            'storage_disk' => $disk,
        ];
    }

    private function urlFor(string $disk, string $path): ?string
    {
        if ($disk === 'r2') {
            return $this->publicUrl($path);
        }

        $diskInstance = Storage::disk($disk);

        return method_exists($diskInstance, 'url') ? $diskInstance->url($path) : null;
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
