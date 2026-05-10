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
            'journals' => $journals->sortByDesc(fn ($journal) => $this->journalSortKey($journal))->values(),
            'groups' => $groups,
        ]);
    }

    public function create(FirebaseService $firebase): View
    {
        $studentsByGroup = collect($firebase->all('students'))
            ->sortBy('name')
            ->groupBy('group_id');
        $journals = collect($firebase->all('journals'));

        return view('journals.create', [
            'groups' => collect($firebase->all('groups'))->sortBy('name')->values(),
            'targets' => collect($firebase->all('targets'))->where('status', 'active')->sortBy('meeting_no')->values(),
            'studentsByGroup' => $studentsByGroup,
            'historicalChecklistByGroup' => $this->historicalChecklistByGroup($journals),
        ]);
    }

    public function store(Request $request, FirebaseService $firebase, CloudflareR2Service $storage): RedirectResponse
    {
        $user = current_user();

        $validated = $request->validate($this->journalRules($user->isTeacher()));
        $this->validateContributionProofs($request, $validated);

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
            $this->uploadContributionImages($request, $firebase, $storage, $groupId, $journalId, $payload['member_contributions'] ?? []);
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
            'documentations' => collect($firebase->all('documentations'))->where('journal_id', $journal)->where('documentation_kind', '!=', 'member_contribution')->values(),
            'contributionDocumentations' => collect($firebase->all('documentations'))->where('journal_id', $journal)->where('documentation_kind', 'member_contribution')->keyBy('id'),
            'feedbacks' => collect($firebase->all('feedbacks'))->where('journal_id', $journal)->sortByDesc('created_at')->values(),
            'comments' => collect($firebase->all('journal_comments'))->where('journal_id', $journal)->sortBy('created_at')->values(),
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
            'documentations' => collect($firebase->all('documentations'))->where('journal_id', $journal)->where('documentation_kind', '!=', 'member_contribution')->values(),
            'studentsByGroup' => collect($firebase->all('students'))->sortBy('name')->groupBy('group_id'),
        ]);
    }

    public function update(string $journal, Request $request, FirebaseService $firebase, CloudflareR2Service $storage): RedirectResponse
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        $user = current_user();
        $validated = $request->validate($this->journalRules($user->isTeacher()));
        $this->validateContributionProofs($request, $validated);
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
            $this->uploadContributionImages($request, $firebase, $storage, $groupId, $journal, $payload['member_contributions'] ?? []);
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

            foreach (collect($firebase->all('journal_comments'))->where('journal_id', $journal) as $comment) {
                if (! empty($comment['id'])) {
                    $firebase->delete('journal_comments', $comment['id']);
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

    public function storeComment(string $journal, Request $request, FirebaseService $firebase): RedirectResponse
    {
        $record = $firebase->find('journals', $journal);
        abort_if(! $record, 404);
        $this->authorizeJournal($record);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $firebase->push('journal_comments', [
                'journal_id' => $journal,
                'group_id' => $record['group_id'] ?? null,
                'message' => $validated['message'],
                'created_by' => (string) current_user()->id,
                'created_by_name' => current_user()->name,
                'created_by_role' => current_user()->role,
            ]);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['message' => 'Gagal menyimpan komentar: '.$e->getMessage()]);
        }

        return back()->with('status', 'Komentar diskusi ditambahkan.');
    }

    public function destroyComment(string $comment, FirebaseService $firebase): RedirectResponse
    {
        $record = $firebase->find('journal_comments', $comment);
        abort_if(! $record, 404);

        $journal = $firebase->find('journals', $record['journal_id'] ?? '');
        abort_if(! $journal, 404);
        $this->authorizeJournal($journal);

        abort_unless(current_user()->isTeacher() || ($record['created_by'] ?? null) === (string) current_user()->id, 403);

        try {
            $firebase->delete('journal_comments', $comment);
        } catch (Throwable $e) {
            return back()->withErrors(['comment' => 'Gagal menghapus komentar: '.$e->getMessage()]);
        }

        return back()->with('status', 'Komentar dihapus.');
    }

    private function authorizeJournal(array $journal): void
    {
        $user = current_user();

        abort_if($user->isStudent() && ($journal['group_id'] ?? null) !== $user->group_id, 403);
    }

    private function journalSortKey(array $journal): string
    {
        return sprintf(
            '%03d-%s-%s',
            (int) ($journal['meeting_no'] ?? 0),
            $journal['journal_date'] ?? '',
            $journal['created_at'] ?? ''
        );
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
            'target_checklist' => ['nullable', 'array'],
            'target_checklist.*' => ['nullable', 'array'],
            'target_checklist.*.completed' => ['nullable', 'boolean'],
            'target_checklist.*.items' => ['nullable', 'array'],
            'target_checklist.*.items.*' => ['string', 'max:500'],
            'member_contributions' => ['nullable', 'array'],
            'member_contributions.*.student_id' => ['required_with:member_contributions', 'string', 'max:120'],
            'member_contributions.*.student_name' => ['required_with:member_contributions', 'string', 'max:120'],
            'member_contributions.*.contribution' => ['nullable', 'string', 'max:1000'],
            'member_contributions.*.photo_documentation_id' => ['nullable', 'string', 'max:120'],
            'contribution_images' => ['nullable', 'array'],
            'contribution_images.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
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
            ->map(function ($value) {
                if ($value === true || $value === 1 || $value === '1') {
                    return true;
                }

                if (! is_array($value)) {
                    return null;
                }

                $items = collect($value['items'] ?? [])
                    ->filter(fn ($item) => filled($item))
                    ->map(fn ($item) => (string) $item)
                    ->all();

                return array_filter([
                    'completed' => filter_var($value['completed'] ?? false, FILTER_VALIDATE_BOOLEAN) ?: null,
                    'items' => empty($items) ? null : $items,
                ], fn ($item) => $item !== null);
            })
            ->filter(fn ($value) => $value === true || (is_array($value) && $value !== []))
            ->all();

        return [
            'group_id' => $groupId,
            'meeting_no' => (int) $validated['meeting_no'],
            'journal_date' => $validated['journal_date'],
            'target_checklist' => empty($checklist) ? null : $checklist,
            'member_contributions' => $this->memberContributionPayload($validated['member_contributions'] ?? []),
            'target_vs_realization' => $validated['target_vs_realization'] ?? null,
            'progress_today' => $validated['progress_today'],
            'data_result' => $validated['data_result'] ?? null,
            'problem' => $validated['problem'] ?? null,
            'solution_next_step' => $validated['solution_next_step'] ?? null,
            'insight' => $validated['insight'] ?? null,
            'help_request' => $request->boolean('help_request'),
        ];
    }

    private function historicalChecklistByGroup($journals): array
    {
        return $journals
            ->groupBy('group_id')
            ->map(function ($groupJournals) {
                return $groupJournals
                    ->reduce(function (array $carry, array $journal) {
                        foreach (($journal['target_checklist'] ?? []) as $targetId => $checked) {
                            if ($checked === true || $checked === 1 || $checked === '1') {
                                $carry[$targetId] = true;

                                continue;
                            }

                            if (! is_array($checked)) {
                                continue;
                            }

                            if (filter_var($checked['completed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                                $carry[$targetId] = true;

                                continue;
                            }

                            foreach (array_keys($checked['items'] ?? []) as $index) {
                                if (($carry[$targetId] ?? null) === true) {
                                    continue;
                                }

                                $carry[$targetId]['items'][(string) $index] = true;
                            }
                        }

                        return $carry;
                    }, []);
            })
            ->all();
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

    private function memberContributionPayload(array $contributions): array
    {
        return collect($contributions)
            ->mapWithKeys(function ($item, $studentId) {
                if (! is_array($item)) {
                    return [];
                }

                $id = (string) ($item['student_id'] ?? $studentId);
                $payload = array_filter([
                    'student_id' => $id,
                    'student_name' => $item['student_name'] ?? null,
                    'contribution' => filled($item['contribution'] ?? null) ? trim((string) $item['contribution']) : null,
                    'photo_documentation_id' => $item['photo_documentation_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== '');

                return $id !== '' ? [$id => $payload] : [];
            })
            ->filter(fn ($item) => ! empty($item['contribution']) || ! empty($item['photo_documentation_id']))
            ->all();
    }

    private function validateContributionProofs(Request $request, array $validated): void
    {
        foreach (($validated['member_contributions'] ?? []) as $studentId => $contribution) {
            if (! is_array($contribution) || blank($contribution['contribution'] ?? null)) {
                continue;
            }

            if ($request->hasFile('contribution_images.'.$studentId) || filled($contribution['photo_documentation_id'] ?? null)) {
                continue;
            }

            validator([], [])->after(function ($validator) use ($studentId) {
                $validator->errors()->add(
                    'contribution_images.'.$studentId,
                    'Setiap kontribusi anggota wajib disertai foto atau screenshot bukti maksimal 5 MB.'
                );
            })->validate();
        }
    }

    private function uploadContributionImages(Request $request, FirebaseService $firebase, CloudflareR2Service $storage, string $groupId, string $journalId, array $contributions): void
    {
        foreach ($request->file('contribution_images', []) as $studentId => $file) {
            if (! $file) {
                continue;
            }

            $studentId = (string) $studentId;
            $metadata = $storage->storeContributionImage($file, $groupId, $journalId, $studentId);
            $documentationId = $firebase->push('documentations', [
                ...$metadata,
                'group_id' => $groupId,
                'journal_id' => $journalId,
                'documentation_kind' => 'member_contribution',
                'student_id' => $studentId,
                'student_name' => $contributions[$studentId]['student_name'] ?? null,
                'uploaded_by' => (string) current_user()->id,
            ]);

            $contributions[$studentId]['photo_documentation_id'] = $documentationId;
        }

        if ($request->hasFile('contribution_images')) {
            $firebase->update('journals', $journalId, [
                'member_contributions' => $contributions,
            ]);
        }
    }
}
