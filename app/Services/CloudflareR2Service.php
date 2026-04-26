<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        return [
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'mime_type' => $file->getClientMimeType(),
            'file_type' => $this->fileType($file),
            'size' => $file->getSize(),
            'storage_disk' => $disk,
        ];
    }

    private function disk(): string
    {
        $hasR2Config = filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'));

        $hasS3Adapter = class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class);

        return $hasR2Config && $hasS3Adapter ? 'r2' : 'public';
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
