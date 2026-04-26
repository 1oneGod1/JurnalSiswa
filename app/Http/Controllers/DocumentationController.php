<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentationController extends Controller
{
    public function show(string $documentation, FirebaseService $firebase): Response|RedirectResponse
    {
        $record = $firebase->find('documentations', $documentation);
        abort_if(! $record, 404);

        $user = auth()->user();
        abort_if($user?->isStudent() && ($record['group_id'] ?? null) !== $user->group_id, 403);

        if (! empty($record['url'])) {
            return redirect()->away($record['url']);
        }

        $disk = $record['storage_disk'] ?? 'public';
        $path = $record['path'] ?? null;
        abort_if(! is_string($path) || $path === '', 404);

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
}
