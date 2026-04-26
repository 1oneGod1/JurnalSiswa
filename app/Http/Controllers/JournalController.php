<?php

namespace App\Http\Controllers;

use App\Services\CloudflareR2Service;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class JournalController extends Controller
{
    public function index(FirebaseService $firebase): View
    {
        $user = auth()->user();
        $groups = collect($firebase->all('groups'))->keyBy('id');
        $journals = collect($firebase->all('journals'));

        if ($user->isStudent()) {
            $journals = $journals->where('group_id', $user->group_id);
        }

        return view('journals.index', [
            'journals' => $journals->sortByDesc(fn ($journal) => $journal['journal_date'] ?? $journal['created_at'] ?? '')->values(),
            'groups' => $groups,
        ]);
    }

    public function create(FirebaseService $firebase): View
    {
        return view('journals.create', [
            'groups' => collect($firebase->all('groups'))->sortBy('name')->values(),
            'targets' => collect($firebase->all('targets'))->where('status', 'active')->sortBy('meeting_no')->values(),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase, CloudflareR2Service $storage): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'group_id' => [$user->isTeacher() ? 'required' : 'nullable', 'string', 'max:80'],
            'meeting_no' => ['required', 'integer', 'min:1', 'max:99'],
            'journal_date' => ['required', 'date'],
            'target_checklist' => ['array'],
            'target_checklist.*' => ['string'],
            'target_vs_realization' => ['nullable', 'string', 'max:1200'],
            'progress_today' => ['required', 'string', 'max:2000'],
            'data_result' => ['nullable', 'string', 'max:2000'],
            'problem' => ['nullable', 'string', 'max:1600'],
            'solution_next_step' => ['nullable', 'string', 'max:1600'],
            'insight' => ['nullable', 'string', 'max:1600'],
            'help_request' => ['nullable', 'boolean'],
            'documentations' => ['nullable', 'array', 'max:6'],
            'documentations.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,pdf,txt', 'max:20480'],
        ]);

        $groupId = $user->isTeacher() ? $validated['group_id'] : $user->group_id;
        abort_if(blank($groupId), 422, 'Akun siswa belum tersambung ke kelompok.');

        $checklist = collect($validated['target_checklist'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [$key => true])
            ->all();

        $payload = array_filter([
            'group_id' => $groupId,
            'meeting_no' => (int) $validated['meeting_no'],
            'journal_date' => $validated['journal_date'],
            'target_checklist' => empty($checklist) ? null : $checklist,
            'target_vs_realization' => $validated['target_vs_realization'] ?? null,
            'progress_today' => $validated['progress_today'],
            'data_result' => $validated['data_result'] ?? null,
            'problem' => $validated['problem'] ?? null,
            'solution_next_step' => $validated['solution_next_step'] ?? null,
            'insight' => $validated['insight'] ?? null,
            'help_request' => $request->boolean('help_request'),
            'created_by' => (string) $user->id,
        ], fn ($value) => $value !== null);

        try {
            $journalId = $firebase->push('journals', $payload);
        } catch (Throwable $e) {
            Log::error('Gagal simpan jurnal ke Firebase', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return back()
                ->withInput()
                ->withErrors(['progress_today' => 'Gagal menyimpan jurnal: '.$e->getMessage()]);
        }

        foreach ($request->file('documentations', []) as $file) {
            try {
                $metadata = $storage->storeDocumentation($file, $groupId, $journalId);

                $firebase->push('documentations', array_filter([
                    ...$metadata,
                    'group_id' => $groupId,
                    'journal_id' => $journalId,
                    'uploaded_by' => (string) $user->id,
                ], fn ($value) => $value !== null));
            } catch (Throwable $e) {
                Log::error('Gagal upload dokumentasi', [
                    'message' => $e->getMessage(),
                    'file' => $file->getClientOriginalName(),
                ]);
            }
        }

        return redirect()
            ->route('journals.show', $journalId)
            ->with('status', 'Jurnal berhasil disimpan.');
    }

    public function show(string $journal, FirebaseService $firebase): View
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        $groups = collect($firebase->all('groups'))->keyBy('id');

        return view('journals.show', [
            'journal' => $record,
            'group' => $groups->get($record['group_id']),
            'targets' => collect($firebase->all('targets'))->keyBy('id'),
            'documentations' => collect($firebase->all('documentations'))->where('journal_id', $journal)->values(),
            'feedbacks' => collect($firebase->all('feedbacks'))->where('journal_id', $journal)->sortByDesc('created_at')->values(),
        ]);
    }

    private function authorizeJournal(array $journal): void
    {
        $user = auth()->user();

        abort_if($user->isStudent() && $journal['group_id'] !== $user->group_id, 403);
    }
}
