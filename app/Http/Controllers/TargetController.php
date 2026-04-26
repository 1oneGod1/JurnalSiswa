<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            'meeting_no' => ['required', 'integer', 'min:1', 'max:99'],
            'target_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'checklist_items' => ['nullable', 'string', 'max:2000'],
        ]);

        $items = collect(preg_split('/\r\n|\r|\n/', (string) ($validated['checklist_items'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        $firebase->push('targets', [
            'title' => $validated['title'],
            'meeting_no' => (int) $validated['meeting_no'],
            'target_date' => $validated['target_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'checklist_items' => $items,
            'status' => 'active',
            'created_by' => (string) $request->user()->id,
        ]);

        return redirect()
            ->route('targets.index')
            ->with('status', 'Target pertemuan berhasil dibuat.');
    }

    public function destroy(string $target, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(auth()->user()->isTeacher(), 403);

        $firebase->update('targets', $target, ['status' => 'archived']);

        return back()->with('status', 'Target diarsipkan.');
    }
}
