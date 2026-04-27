<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class TargetController extends Controller
{
    public function index(FirebaseService $firebase): View
    {
        return view('targets.index', [
            'targets' => collect($firebase->all('targets'))
                ->sortByDesc(function ($target) {
                    $date = $target['target_date']
                        ?? $target['week_start']
                        ?? $target['created_at']
                        ?? '0000-00-00';

                    return $date.'-'.($target['created_at'] ?? '');
                })
                ->values(),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()->isTeacher(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'mode' => ['required', 'in:pertemuan,mingguan'],
            'meeting_no' => ['nullable', 'integer', 'min:1', 'max:99'],
            'week_no' => ['nullable', 'integer', 'min:1', 'max:52'],
            'target_date' => ['nullable', 'date'],
            'week_start' => ['nullable', 'date'],
            'week_end' => ['nullable', 'date', 'after_or_equal:week_start'],
            'description' => ['nullable', 'string', 'max:1000'],
            'checklist_items' => ['nullable', 'string', 'max:2000'],
        ]);

        $items = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['checklist_items'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'title' => $validated['title'],
            'mode' => $validated['mode'],
            'description' => $validated['description'] ?? null,
            'checklist_items' => $items,
            'status' => 'active',
            'created_by' => (string) current_user()->id,
        ];

        if ($validated['mode'] === 'pertemuan') {
            $payload['meeting_no'] = (int) ($validated['meeting_no'] ?? 1);
            if (! empty($validated['target_date'])) {
                $payload['target_date'] = $validated['target_date'];
            }
        } else {
            $payload['week_no'] = (int) ($validated['week_no'] ?? 1);
            if (! empty($validated['week_start'])) {
                $payload['week_start'] = $validated['week_start'];
            }
            if (! empty($validated['week_end'])) {
                $payload['week_end'] = $validated['week_end'];
            }
        }

        $payload = array_filter($payload, fn ($value) => $value !== null);

        try {
            $firebase->push('targets', $payload);
        } catch (Throwable $e) {
            Log::error('Gagal simpan target', ['payload' => $payload, 'message' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['title' => 'Gagal menyimpan target: '.$e->getMessage()]);
        }

        return redirect()
            ->route('targets.index')
            ->with('status', 'Target pertemuan berhasil dibuat.');
    }

    public function archive(string $target, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()->isTeacher(), 403);

        try {
            $firebase->update('targets', $target, ['status' => 'archived']);
        } catch (Throwable $e) {
            return back()->withErrors(['target' => 'Gagal mengarsipkan target: '.$e->getMessage()]);
        }

        return back()->with('status', 'Target diarsipkan.');
    }

    public function destroy(string $target, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()->isTeacher(), 403);

        try {
            $firebase->delete('targets', $target);
        } catch (Throwable $e) {
            return back()->withErrors(['target' => 'Gagal menghapus target: '.$e->getMessage()]);
        }

        return back()->with('status', 'Target dihapus permanen.');
    }
}
