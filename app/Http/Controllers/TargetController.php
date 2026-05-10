<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TargetController extends Controller
{
    public function index(Request $request, FirebaseService $firebase): View
    {
        $user = current_user();
        $scope = $this->progressScope();
        $progressByTarget = collect($firebase->all('target_progress'))
            ->where('scope_key', $scope['key'])
            ->keyBy('target_id');
        $journals = collect($firebase->all('journals'));

        if ($scope['type'] === 'group') {
            $journals = $journals->where('group_id', $user->group_id);
        } else {
            $journals = $journals->where('created_by', (string) $user->id);
        }

        $allTargets = collect($firebase->all('targets'))
            ->map(function ($target) use ($progressByTarget, $journals) {
                $progress = $progressByTarget->get($target['id'], []);
                $totalItems = count($target['checklist_items'] ?? []);
                $savedCheckedItems = collect($progress['checked_items'] ?? [])
                    ->filter()
                    ->keys()
                    ->map(fn ($index) => (string) $index)
                    ->values()
                    ->all();
                $journalCheckedItems = $this->journalCheckedItems($target, $journals);
                $checkedItems = collect([...$savedCheckedItems, ...$journalCheckedItems])
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $completedCount = count($checkedItems);

                return [
                    ...$target,
                    'checked_items' => $checkedItems,
                    'completed_count' => $completedCount,
                    'total_count' => $totalItems,
                    'progress_percent' => $totalItems > 0 ? (int) round(($completedCount / $totalItems) * 100) : 0,
                    'computed_status' => $this->computedStatus($target, $checkedItems),
                ];
            })
            ->sortByDesc(function ($target) {
                $date = $target['target_date']
                    ?? $target['week_start']
                    ?? $target['created_at']
                    ?? '0000-00-00';

                return $date.'-'.($target['created_at'] ?? '');
            })
            ->values();

        $filters = [
            'status' => $request->query('status', 'all'),
            'mode' => $request->query('mode', 'all'),
            'q' => trim((string) $request->query('q', '')),
        ];

        $targets = $allTargets
            ->when($filters['status'] !== 'all', fn ($items) => $items->where('computed_status', $filters['status']))
            ->when($filters['mode'] !== 'all', fn ($items) => $items->where('mode', $filters['mode']))
            ->when($filters['q'] !== '', function ($items) use ($filters) {
                return $items->filter(function ($target) use ($filters) {
                    $haystack = strtolower(($target['title'] ?? '').' '.($target['description'] ?? ''));

                    return str_contains($haystack, strtolower($filters['q']));
                });
            })
            ->values();

        $visibleScheduleScopes = $this->visibleScheduleScopeKeys();
        $schedules = collect($firebase->all('schedules'))
            ->filter(fn ($schedule) => in_array($schedule['scope_key'] ?? '', $visibleScheduleScopes, true))
            ->map(fn ($schedule) => [
                ...$schedule,
                'can_delete' => $this->canDeleteSchedule($schedule),
                'scope_label' => ($schedule['scope_type'] ?? '') === 'global' ? 'Semua akun' : 'Pribadi',
            ])
            ->sortBy('date')
            ->values();

        return view('targets.index', [
            'targets' => $targets,
            'schedules' => $schedules,
            'filters' => $filters,
            'targetCounts' => [
                'all' => $allTargets->count(),
                'belum mulai' => $allTargets->where('computed_status', 'belum mulai')->count(),
                'proses' => $allTargets->where('computed_status', 'proses')->count(),
                'selesai' => $allTargets->where('computed_status', 'selesai')->count(),
                'terlambat' => $allTargets->where('computed_status', 'terlambat')->count(),
            ],
        ]);
    }

    public function calendar(FirebaseService $firebase): View
    {
        $user = current_user();
        $scope = $this->progressScope();
        $progressByTarget = collect($firebase->all('target_progress'))
            ->where('scope_key', $scope['key'])
            ->keyBy('target_id');
        $journals = collect($firebase->all('journals'));

        if ($scope['type'] === 'group') {
            $journals = $journals->where('group_id', $user->group_id);
        } else {
            $journals = $journals->where('created_by', (string) $user->id);
        }

        $targets = collect($firebase->all('targets'))
            ->map(function ($target) use ($progressByTarget, $journals) {
                $progress = $progressByTarget->get($target['id'], []);
                $totalItems = count($target['checklist_items'] ?? []);
                $savedCheckedItems = collect($progress['checked_items'] ?? [])
                    ->filter()
                    ->keys()
                    ->map(fn ($index) => (string) $index)
                    ->values()
                    ->all();
                $journalCheckedItems = $this->journalCheckedItems($target, $journals);
                $checkedItems = collect([...$savedCheckedItems, ...$journalCheckedItems])
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $completedCount = count($checkedItems);

                return [
                    ...$target,
                    'checked_items' => $checkedItems,
                    'completed_count' => $completedCount,
                    'total_count' => $totalItems,
                    'progress_percent' => $totalItems > 0 ? (int) round(($completedCount / $totalItems) * 100) : 0,
                    'computed_status' => $this->computedStatus($target, $checkedItems),
                ];
            })
            ->sortBy(function ($target) {
                return $target['target_date']
                    ?? $target['week_start']
                    ?? $target['created_at']
                    ?? '9999-12-31';
            })
            ->values();

        $visibleScheduleScopes = $this->visibleScheduleScopeKeys();
        $schedules = collect($firebase->all('schedules'))
            ->filter(fn ($schedule) => in_array($schedule['scope_key'] ?? '', $visibleScheduleScopes, true))
            ->map(fn ($schedule) => [
                ...$schedule,
                'can_delete' => $this->canDeleteSchedule($schedule),
                'scope_label' => ($schedule['scope_type'] ?? '') === 'global' ? 'Semua akun' : 'Pribadi',
            ])
            ->sortBy('date')
            ->values();

        return view('calendar.index', [
            'targets' => $targets,
            'schedules' => $schedules,
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

    public function updateChecklist(Request $request, string $target, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()->isStudent(), 403);

        $record = $firebase->find('targets', $target);

        if (! $record) {
            return back()->withErrors(['target' => 'Target tidak ditemukan.']);
        }

        $items = $record['checklist_items'] ?? [];
        $checkedItems = collect($request->input('checklist', []))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($index) => array_key_exists($index, $items))
            ->unique()
            ->sort()
            ->values();

        $scope = $this->progressScope();
        $checkedMap = $checkedItems
            ->mapWithKeys(fn ($index) => [(string) $index => true])
            ->all();

        try {
            $firebase->set('target_progress', $this->progressId($scope['key'], $target), [
                'target_id' => $target,
                'scope_key' => $scope['key'],
                'scope_type' => $scope['type'],
                'group_id' => current_user()->group_id,
                'user_id' => current_user()->id,
                'checked_items' => $checkedMap,
                'completed_count' => count($checkedMap),
                'total_count' => count($items),
                'status' => $this->computedStatus($record, array_keys($checkedMap)),
            ]);
        } catch (Throwable $e) {
            return back()->withErrors(['target' => 'Gagal menyimpan checklist: '.$e->getMessage()]);
        }

        return back()->with('status', 'Checklist target tersimpan.');
    }

    public function storeSchedule(Request $request, FirebaseService $firebase): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:penting,target-pribadi'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $scope = $this->scheduleScope();

        try {
            $firebase->push('schedules', [
                'title' => $validated['title'],
                'date' => $validated['date'],
                'type' => $validated['type'],
                'note' => $validated['note'] ?? null,
                'scope_key' => $scope['key'],
                'scope_type' => $scope['type'],
                'group_id' => current_user()->group_id,
                'created_by' => current_user()->id,
            ]);
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['schedule' => 'Gagal menyimpan jadwal: '.$e->getMessage()]);
        }

        return back()->with('status', 'Jadwal penting berhasil ditambahkan.');
    }

    public function destroySchedule(string $schedule, FirebaseService $firebase): RedirectResponse
    {
        $record = $firebase->find('schedules', $schedule);

        if (! $record || ! $this->canDeleteSchedule($record)) {
            return back()->withErrors(['schedule' => 'Jadwal tidak ditemukan.']);
        }

        try {
            $firebase->delete('schedules', $schedule);
        } catch (Throwable $e) {
            return back()->withErrors(['schedule' => 'Gagal menghapus jadwal: '.$e->getMessage()]);
        }

        return back()->with('status', 'Jadwal dihapus.');
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

    private function progressScope(): array
    {
        $user = current_user();
        $groupId = $user->group_id;
        $rawKey = filled($groupId) ? 'group_'.$groupId : 'user_'.$user->id;

        return [
            'key' => Str::slug($rawKey, '_'),
            'type' => filled($groupId) ? 'group' : 'user',
        ];
    }

    private function scheduleScope(): array
    {
        if (current_user()->isTeacher()) {
            return [
                'key' => 'global',
                'type' => 'global',
            ];
        }

        return [
            'key' => Str::slug('user_'.current_user()->id, '_'),
            'type' => 'user',
        ];
    }

    private function visibleScheduleScopeKeys(): array
    {
        return [
            'global',
            Str::slug('user_'.current_user()->id, '_'),
        ];
    }

    private function canDeleteSchedule(array $schedule): bool
    {
        $scopeKey = $schedule['scope_key'] ?? null;

        if ($scopeKey === 'global') {
            return current_user()->isTeacher();
        }

        return $scopeKey === Str::slug('user_'.current_user()->id, '_');
    }

    private function journalCheckedItems(array $target, $journals): array
    {
        $targetId = $target['id'] ?? null;

        if (! $targetId) {
            return [];
        }

        $items = $target['checklist_items'] ?? [];
        $allIndexes = collect(array_keys($items))
            ->map(fn ($index) => (string) $index)
            ->all();

        return $journals
            ->map(fn ($journal) => data_get($journal, 'target_checklist.'.$targetId))
            ->filter()
            ->flatMap(function ($checked) use ($allIndexes) {
                if ($checked === true || $checked === 1 || $checked === '1') {
                    return $allIndexes;
                }

                if (! is_array($checked)) {
                    return [];
                }

                if (filter_var($checked['completed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    return $allIndexes;
                }

                return collect($checked['items'] ?? [])
                    ->keys()
                    ->map(fn ($index) => (string) $index)
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    private function progressId(string $scopeKey, string $targetId): string
    {
        return Str::slug($scopeKey.'_'.$targetId, '_');
    }

    private function computedStatus(array $target, array $checkedItems): string
    {
        if (($target['status'] ?? 'active') === 'archived') {
            return 'diarsipkan';
        }

        $totalItems = count($target['checklist_items'] ?? []);
        $completedCount = count($checkedItems);

        if ($totalItems > 0 && $completedCount >= $totalItems) {
            return 'selesai';
        }

        $dueDate = $target['target_date'] ?? $target['week_end'] ?? null;

        if ($dueDate && Carbon::parse($dueDate)->isPast() && ! Carbon::parse($dueDate)->isToday()) {
            return 'terlambat';
        }

        if ($completedCount > 0) {
            return 'proses';
        }

        return 'belum mulai';
    }
}
