<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class TargetController extends Controller
{
    public function index(FirebaseService $firebase): View
    {
        return view('targets.index', [
            'targets' => collect($firebase->all('targets'))
                ->sortByDesc(fn ($target) => sprintf('%03d-%s', $target['meeting_no'] ?? 0, $target['created_at'] ?? ''))
                ->values(),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase): RedirectResponse
    {
        abort_unless($request->user()->isTeacher(), 403);

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

        try {
            $firebase->push('targets', [
                'title' => $validated['title'],
                'mode' => $validated['mode'],
                'meeting_no' => $validated['mode'] === 'pertemuan' ? (int) ($validated['meeting_no'] ?? 1) : null,
                'week_no' => $validated['mode'] === 'mingguan' ? (int) ($validated['week_no'] ?? 1) : null,
                'target_date' => $validated['target_date'] ?? null,
                'week_start' => $validated['mode'] === 'mingguan' ? ($validated['week_start'] ?? null) : null,
                'week_end' => $validated['mode'] === 'mingguan' ? ($validated['week_end'] ?? null) : null,
                'description' => $validated['description'] ?? null,
                'checklist_items' => $items,
                'status' => 'active',
                'created_by' => (string) $request->user()->id,
            ]);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['title' => 'Gagal menyimpan target: '.$e->getMessage()]);
        }

        return redirect()
            ->route('targets.index')
            ->with('status', 'Target pertemuan berhasil dibuat.');
    }

    public function destroy(string $target, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(auth()->user()->isTeacher(), 403);

        try {
            $firebase->update('targets', $target, ['status' => 'archived']);
        } catch (Throwable $e) {
            return back()->withErrors(['target' => 'Gagal mengarsipkan target: '.$e->getMessage()]);
        }

        return back()->with('status', 'Target diarsipkan.');
    }
}
