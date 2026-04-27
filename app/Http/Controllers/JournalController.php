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
        $user = current_user();
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
        $user = current_user();

        $validated = $request->validate($this->journalRules($user->isTeacher()));

        $groupId = $user->isTeacher() ? $validated['group_id'] : $user->group_id;
        abort_if(blank($groupId), 422, 'Akun siswa belum tersambung ke kelompok.');

        $payload = [
            ...$this->journalPayload($validated, $request, $groupId),
            'created_by' => (string) $user->id,
        ];

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

        try {
            $this->uploadDocumentations($request, $firebase, $storage, $groupId, $journalId);
        } catch (Throwable $e) {
            Log::error('Gagal upload dokumentasi', [
                'message' => $e->getMessage(),
                'journal_id' => $journalId,
            ]);

            return redirect()
                ->route('journals.edit', $journalId)
                ->withErrors(['documentations' => 'Jurnal tersimpan, tapi dokumentasi gagal diupload: '.$e->getMessage()]);
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

    public function edit(string $journal, FirebaseService $firebase): View
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        return view('journals.edit', [
            'journal' => $record,
            'groups' => collect($firebase->all('groups'))->sortBy('name')->values(),
            'targets' => collect($firebase->all('targets'))->where('status', 'active')->sortBy('meeting_no')->values(),
            'documentations' => collect($firebase->all('documentations'))->where('journal_id', $journal)->values(),
        ]);
    }

    public function update(string $journal, Request $request, FirebaseService $firebase, CloudflareR2Service $storage): RedirectResponse
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        $user = current_user();
        $validated = $request->validate($this->journalRules($user->isTeacher()));
        $groupId = $user->isTeacher() ? $validated['group_id'] : ($record['group_id'] ?? $user->group_id);
        abort_if(blank($groupId), 422, 'Akun siswa belum tersambung ke kelompok.');

        $payload = [
            ...$this->journalPayload($validated, $request, $groupId),
            'updated_by' => (string) $user->id,
        ];

        try {
            $firebase->update('journals', $journal, $payload);
        } catch (Throwable $e) {
            Log::error('Gagal update jurnal ke Firebase', [
                'message' => $e->getMessage(),
                'journal_id' => $journal,
                'payload' => $payload,
            ]);

            return back()
                ->withInput()
                ->withErrors(['progress_today' => 'Gagal mengupdate jurnal: '.$e->getMessage()]);
        }

        try {
            $this->uploadDocumentations($request, $firebase, $storage, $groupId, $journal);
        } catch (Throwable $e) {
            Log::error('Gagal upload dokumentasi saat update jurnal', [
                'message' => $e->getMessage(),
                'journal_id' => $journal,
            ]);

            return back()
                ->withInput()
                ->withErrors(['documentations' => 'Perubahan jurnal tersimpan, tapi dokumentasi gagal diupload: '.$e->getMessage()]);
        }

        return redirect()
            ->route('journals.show', $journal)
            ->with('status', 'Jurnal berhasil diperbarui.');
    }

    public function destroy(string $journal, FirebaseService $firebase): RedirectResponse
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        try {
            foreach (collect($firebase->all('documentations'))->where('journal_id', $journal) as $doc) {
                if (! empty($doc['id'])) {
                    $firebase->delete('documentations', $doc['id']);
                }
            }

            foreach (collect($firebase->all('feedbacks'))->where('journal_id', $journal) as $fb) {
                if (! empty($fb['id'])) {
                    $firebase->delete('feedbacks', $fb['id']);
                }
            }

            $firebase->delete('journals', $journal);
        } catch (Throwable $e) {
            Log::error('Gagal hapus jurnal', ['journal_id' => $journal, 'message' => $e->getMessage()]);

            return back()->withErrors(['journal' => 'Gagal menghapus jurnal: '.$e->getMessage()]);
        }

        return redirect()
            ->route('journals.index')
            ->with('status', 'Jurnal dihapus.');
    }

    private function authorizeJournal(array $journal): void
    {
        $user = current_user();

        abort_if($user->isStudent() && ($journal['group_id'] ?? null) !== $user->group_id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function journalRules(bool $requiresGroup): array
    {
        return [
            'group_id' => [$requiresGroup ? 'required' : 'nullable', 'string', 'max:80'],
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
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function journalPayload(array $validated, Request $request, string $groupId): array
    {
        $checklist = collect($validated['target_checklist'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [$key => true])
            ->all();

        return [
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
        ];
    }

    private function uploadDocumentations(Request $request, FirebaseService $firebase, CloudflareR2Service $storage, string $groupId, string $journalId): void
    {
        foreach ($request->file('documentations', []) as $file) {
            $metadata = $storage->storeDocumentation($file, $groupId, $journalId);

            $firebase->push('documentations', [
                ...$metadata,
                'group_id' => $groupId,
                'journal_id' => $journalId,
                'uploaded_by' => (string) current_user()->id,
            ]);
        }
    }
}
