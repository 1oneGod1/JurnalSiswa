<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CloudflareR2Service
{
    public function storeDocumentation(UploadedFile $file, string $groupId, string $journalId): array
    {
        $disk = $this->disk();
        $directory = 'documentations/'.$groupId.'/'.$journalId;
        $filename = now()->format('YmdHis').'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $filename = trim($filename, '-').($extension ? '.'.$extension : '');

        $path = Storage::disk($disk)->putFileAs($directory, $file, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Upload file gagal disimpan.');
        }

        return array_filter([
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $this->publicUrl($disk, $path),
            'mime_type' => $file->getClientMimeType(),
            'file_type' => $this->fileType($file),
            'size' => $file->getSize(),
            'storage_disk' => $disk,
        ], fn ($value) => $value !== null);
    }

    private function publicUrl(string $disk, string $path): ?string
    {
        if ($disk === 'r2') {
            $base = rtrim((string) config('filesystems.disks.r2.url', ''), '/');

            return filled($base) ? $base.'/'.ltrim($path, '/') : null;
        }

        return Storage::disk($disk)->url($path);
    }

    private function disk(): string
    {
        $r2 = config('filesystems.disks.r2');
        $hasR2Config = filled($r2['key'] ?? null)
            && filled($r2['secret'] ?? null)
            && filled($r2['bucket'] ?? null)
            && filled($r2['endpoint'] ?? null);
        $hasAnyR2Config = filled($r2['key'] ?? null)
            || filled($r2['secret'] ?? null)
            || filled($r2['bucket'] ?? null)
            || filled($r2['endpoint'] ?? null);
        $wantsR2 = config('filesystems.default') === 'r2' || $hasAnyR2Config;

        $hasS3Adapter = class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class);

        if (! $hasR2Config) {
            if ($wantsR2) {
                throw new RuntimeException('Konfigurasi R2 belum lengkap. Isi R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET, dan R2_ENDPOINT.');
            }

            return 'public';
        }

        if (! $hasS3Adapter) {
            throw new RuntimeException('Package league/flysystem-aws-s3-v3 belum terinstall.');
        }

        $endpointPath = parse_url((string) $r2['endpoint'], PHP_URL_PATH);
        if (filled($endpointPath) && $endpointPath !== '/') {
            throw new RuntimeException('R2_ENDPOINT jangan memakai nama bucket. Gunakan format https://<ACCOUNT_ID>.r2.cloudflarestorage.com');
        }

        return 'r2';
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
