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
        $bucket = (string) env('FIREBASE_STORAGE_BUCKET', 'jurnalsiswa-eb7e4.firebasestorage.app');
        $token = Str::uuid()->toString();
        $contents = file_get_contents($file->getRealPath());
        $mime = $file->getClientMimeType() ?: 'application/octet-stream';

        $endpoint = 'https://firebasestorage.googleapis.com/v0/b/'.$bucket.'/o?uploadType=media&name='.urlencode($path);

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => $mime,
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
        $downloadToken = $data['downloadTokens'] ?? $token;

        $url = sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media&token=%s',
            $bucket,
            rawurlencode($path),
            $downloadToken
        );

        return [
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => basename($path),
            'path' => $path,
            'url' => $url,
            'mime_type' => $mime,
            'file_type' => $this->fileType($file),
            'size' => (int) ($data['size'] ?? $file->getSize()),
            'storage_disk' => 'firebase',
        ];
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
