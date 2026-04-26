<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FirebaseService
{
    public function all(string $path): array
    {
        $data = $this->get($path);

        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->map(fn ($item, $id) => is_array($item) ? ['id' => (string) $id, ...$item] : ['id' => (string) $id, 'value' => $item])
            ->values()
            ->all();
    }

    public function keyed(string $path): array
    {
        $data = $this->get($path);

        return is_array($data) ? $data : [];
    }

    public function find(string $path, string $id): ?array
    {
        $record = $this->get($this->join($path, $id));

        if (! is_array($record)) {
            return null;
        }

        return ['id' => $id, ...$record];
    }

    public function push(string $path, array $payload): string
    {
        $payload = $this->withTimestamps($payload);

        if ($this->usesRemoteFirebase()) {
            $response = Http::acceptJson()->post($this->url($path), $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Firebase write failed: '.$response->body());
            }

            return (string) $response->json('name');
        }

        $id = Str::slug(Str::beforeLast($path, 's') ?: $path).'_'.Str::lower(Str::random(10));
        $database = $this->localDatabase();
        Arr::set($database, $this->dot($this->join($path, $id)), $payload);
        $this->writeLocalDatabase($database);

        return $id;
    }

    public function set(string $path, string $id, array $payload): void
    {
        $payload = $this->withTimestamps($payload, $this->find($path, $id));

        if ($this->usesRemoteFirebase()) {
            $response = Http::acceptJson()->put($this->url($this->join($path, $id)), $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Firebase set failed: '.$response->body());
            }

            return;
        }

        $database = $this->localDatabase();
        Arr::set($database, $this->dot($this->join($path, $id)), $payload);
        $this->writeLocalDatabase($database);
    }

    public function update(string $path, string $id, array $payload): void
    {
        $payload = [
            ...$payload,
            'updated_at' => now()->toISOString(),
        ];

        if ($this->usesRemoteFirebase()) {
            $response = Http::acceptJson()->patch($this->url($this->join($path, $id)), $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Firebase update failed: '.$response->body());
            }

            return;
        }

        $database = $this->localDatabase();
        $key = $this->dot($this->join($path, $id));
        $current = Arr::get($database, $key, []);
        Arr::set($database, $key, array_merge(is_array($current) ? $current : [], $payload));
        $this->writeLocalDatabase($database);
    }

    public function delete(string $path, string $id): void
    {
        if ($this->usesRemoteFirebase()) {
            $response = Http::delete($this->url($this->join($path, $id)));

            if (! $response->successful()) {
                throw new RuntimeException('Firebase delete failed: '.$response->body());
            }

            return;
        }

        $database = $this->localDatabase();
        Arr::forget($database, $this->dot($this->join($path, $id)));
        $this->writeLocalDatabase($database);
    }

    private function get(string $path): mixed
    {
        if ($this->usesRemoteFirebase()) {
            try {
                $response = Http::acceptJson()->get($this->url($path));
            } catch (ConnectionException) {
                return [];
            }

            return $response->successful() ? $response->json() : [];
        }

        return Arr::get($this->localDatabase(), $this->dot($path), []);
    }

    private function withTimestamps(array $payload, ?array $current = null): array
    {
        $now = now()->toISOString();

        return [
            ...$payload,
            'created_at' => $current['created_at'] ?? $payload['created_at'] ?? $now,
            'updated_at' => $now,
        ];
    }

    private function usesRemoteFirebase(): bool
    {
        return filled(config('services.firebase.database_url'));
    }

    private function url(string $path): string
    {
        $url = rtrim((string) config('services.firebase.database_url'), '/').'/'.trim($path, '/').'.json';
        $token = config('services.firebase.auth_token');

        return filled($token) ? $url.'?auth='.urlencode((string) $token) : $url;
    }

    private function localDatabase(): array
    {
        $path = $this->localPath();

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode([], JSON_PRETTY_PRINT));
        }

        $data = json_decode((string) File::get($path), true);

        return is_array($data) ? $data : [];
    }

    private function writeLocalDatabase(array $database): void
    {
        File::ensureDirectoryExists(dirname($this->localPath()));
        File::put($this->localPath(), json_encode($database, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function localPath(): string
    {
        return storage_path('app/firebase/local-database.json');
    }

    private function join(string $path, string $id): string
    {
        return trim($path, '/').'/'.trim($id, '/');
    }

    private function dot(string $path): string
    {
        return str_replace('/', '.', trim($path, '/'));
    }
}
