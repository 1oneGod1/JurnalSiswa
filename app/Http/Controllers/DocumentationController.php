<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentationController extends Controller
{
    public function show(string $documentation, FirebaseService $firebase): Response
    {
        $record = $firebase->find('documentations', $documentation);
        abort_if(! $record, 404);

        $user = current_user();
        abort_if($user?->isStudent() && ($record['group_id'] ?? null) !== $user->group_id, 403);

        $disk = $record['storage_disk'] ?? 'public';
        $path = $record['path'] ?? null;
        abort_if(! is_string($path) || $path === '', 404);

        if ($disk === 'firebase') {
            return $this->showFirebaseStorage($record);
        }

        try {
            return Storage::disk($disk)->response(
                $path,
                $record['file_name'] ?? basename($path),
                array_filter(['Content-Type' => $record['mime_type'] ?? null])
            );
        } catch (Throwable) {
            abort(404);
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function showFirebaseStorage(array $record): Response
    {
        foreach ($this->firebaseStorageUrls($record) as $url) {
            $download = $this->downloadFirebaseStorageUrl($url);

            if ($download instanceof Response) {
                return $download;
            }
        }

        $repairedUrl = $this->repairFirebaseStorageUrl($record);
        if (is_string($repairedUrl)) {
            $download = $this->downloadFirebaseStorageUrl($repairedUrl);

            if ($download instanceof Response) {
                return $download;
            }
        }

        abort(404);
    }

    private function downloadFirebaseStorageUrl(string $url): ?Response
    {
        try {
            $response = Http::timeout(120)->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $contentType = (string) ($response->header('Content-Type') ?? 'application/octet-stream');

        return response($response->body(), 200, [
            'Cache-Control' => 'private, max-age=300',
            'Content-Type' => Str::before($contentType, ';') ?: 'application/octet-stream',
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<int, string>
     */
    private function firebaseStorageUrls(array $record): array
    {
        $path = $record['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return [];
        }

        $bucket = (string) config('services.firebase.storage_bucket', 'jurnalsiswa-eb7e4.firebasestorage.app');
        if ($bucket === '') {
            return [];
        }

        $urls = [];

        if (filled($record['url'] ?? null)) {
            $urls[] = (string) $record['url'];
        }

        $token = $record['download_token'] ?? $this->tokenFromUrl($record['url'] ?? null);
        if (filled($token)) {
            $urls[] = $this->firebaseDownloadUrl($bucket, $path, (string) $token);
        }

        $urls[] = $this->firebaseDownloadUrl($bucket, $path);

        return array_values(array_unique(array_filter($urls)));
    }

    private function firebaseDownloadUrl(string $bucket, string $path, ?string $token = null): string
    {
        $url = sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s?alt=media',
            $bucket,
            rawurlencode($path)
        );

        return filled($token) ? $url.'&token='.urlencode($token) : $url;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function repairFirebaseStorageUrl(array $record): ?string
    {
        $path = $record['path'] ?? null;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $bucket = (string) config('services.firebase.storage_bucket', 'jurnalsiswa-eb7e4.firebasestorage.app');
        if ($bucket === '') {
            return null;
        }

        $token = Str::uuid()->toString();
        $endpoint = sprintf(
            'https://firebasestorage.googleapis.com/v0/b/%s/o/%s',
            $bucket,
            rawurlencode($path)
        );

        try {
            $response = Http::timeout(30)->patch($endpoint, [
                'metadata' => [
                    'firebaseStorageDownloadTokens' => $token,
                ],
            ]);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $this->firebaseDownloadUrl($bucket, $path, $token) : null;
    }

    private function tokenFromUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return isset($query['token']) && is_string($query['token']) ? $query['token'] : null;
    }
}
