<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        return $this->uploadToFirebaseStorage($file, $path);
    }

    private function uploadToFirebaseStorage(UploadedFile $file, string $path): array
    {
        $bucket = (string) config('services.firebase.storage_bucket', 'jurnalsiswa-eb7e4.firebasestorage.app');
        $token = Str::uuid()->toString();
        $contents = file_get_contents($file->getRealPath());
        $mime = $file->getClientMimeType() ?: 'application/octet-stream';

        $endpoint = 'https://firebasestorage.googleapis.com/v0/b/'.$bucket.'/o?uploadType=media&name='.urlencode($path);

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => $mime,
                'X-Goog-Meta-FirebaseStorageDownloadTokens' => $token,
                'X-Goog-Upload-File-Name' => basename($path),
                'X-Goog-Upload-Protocol' => 'raw',
            ])
            ->withBody($contents, $mime)
            ->post($endpoint);

        if (! $response->successful()) {
            Log::error('Firebase Storage upload gagal', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
                'path' => $path,
            ]);

            throw new RuntimeException('Upload Firebase Storage gagal ('.$response->status().'): '.Str::limit($response->body(), 200));
        }

        $data = $response->json() ?? [];
        $downloadToken = $data['downloadTokens'] ?? data_get($data, 'metadata.firebaseStorageDownloadTokens');

        if (! filled($downloadToken)) {
            $downloadToken = $this->setFirebaseDownloadToken($bucket, $path, $token) ? $token : null;
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $this->firebaseDownloadUrl($bucket, $path, $downloadToken),
            'download_token' => $downloadToken,
            'mime_type' => $mime,
            'file_type' => $this->fileType($file),
            'size' => (int) ($data['size'] ?? $file->getSize()),
            'storage_disk' => 'firebase',
        ];
    }

    private function firebaseDownloadUrl(string $bucket, string $path, ?string $token): string
    {
        $url = sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
            $bucket,
            rawurlencode($path)
        );

        return filled($token) ? $url.'&token='.urlencode($token) : $url;
    }

    private function setFirebaseDownloadToken(string $bucket, string $path, string $token): bool
    {
        $endpoint = sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s',
            $bucket,
            rawurlencode($path)
        );

        $response = Http::timeout(30)->patch($endpoint, [
            'metadata' => [
                'firebaseStorageDownloadTokens' => $token,
            ],
        ]);

        if ($response->successful()) {
            return true;
        }

        Log::warning('Gagal menulis download token Firebase Storage', [
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 300),
            'path' => $path,
        ]);

        return false;
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
