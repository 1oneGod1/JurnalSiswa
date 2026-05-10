<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class GroupController extends Controller
{
    public function index(FirebaseService $firebase): View
    {
        abort_unless(current_user()?->isTeacher(), 403);

        $groups = collect($firebase->all('groups'))
            ->sortBy(fn ($group) => (int) ($group['number'] ?? 0))
            ->values();

        $studentsByGroup = collect($firebase->all('students'))
            ->sortBy(fn ($student) => $student['name'] ?? '')
            ->groupBy('group_id');

        return view('groups.index', [
            'groups' => $groups,
            'studentsByGroup' => $studentsByGroup,
        ]);
    }

    public function show(string $group, FirebaseService $firebase): View
    {
        $user = current_user();
        abort_if($user->isStudent() && $user->group_id !== $group, 403);

        $record = $firebase->find('groups', $group);
        abort_if(! $record, 404);

        $targets = collect($firebase->all('targets'))->where('status', 'active')->values();
        $journals = collect($firebase->all('journals'))
            ->where('group_id', $group)
            ->sortByDesc(fn ($journal) => sprintf('%03d-%s-%s', (int) ($journal['meeting_no'] ?? 0), $journal['journal_date'] ?? '', $journal['created_at'] ?? ''))
            ->values();
        $feedbacks = collect($firebase->all('feedbacks'))->where('group_id', $group)->sortByDesc('created_at')->values();
        $documentations = collect($firebase->all('documentations'))->where('group_id', $group)->values();
        $members = collect($firebase->all('students'))->where('group_id', $group)->sortBy('name')->values();

        $targetProgress = $targets->map(function ($target) use ($journals) {
            $items = $target['checklist_items'] ?? [];
            $totalItems = count($items);
            $checked = $journals
                ->map(fn ($journal) => data_get($journal, 'target_checklist.'.($target['id'] ?? '')))
                ->filter()
                ->flatMap(function ($checked) use ($items) {
                    if ($checked === true || $checked === 1 || $checked === '1' || filter_var($checked['completed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        return array_keys($items);
                    }

                    return is_array($checked) ? collect($checked['items'] ?? [])->keys()->all() : [];
                })
                ->unique()
                ->count();

            return [
                'target' => $target,
                'checked' => min($checked, $totalItems),
                'total' => $totalItems,
                'complete' => $totalItems > 0 && $checked >= $totalItems,
            ];
        })->values();
        $checkedTargets = $targetProgress->where('complete', true)->count();
        $totalChecklistItems = max((int) $targetProgress->sum('total'), 1);
        $progress = (int) round(($targetProgress->sum('checked') / $totalChecklistItems) * 100);

        return view('groups.show', [
            'group' => $record,
            'members' => $members,
            'journals' => $journals,
            'targets' => $targets,
            'feedbacks' => $feedbacks,
            'documentations' => $documentations,
            'progress' => $progress,
            'checkedTargets' => $checkedTargets,
            'targetProgress' => $targetProgress,
        ]);
    }

    public function store(Request $request, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()?->isTeacher(), 403);

        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:99'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $id = 'kelompok-'.$data['number'];
        try {
            $firebase->set('groups', $id, [
                'number' => (int) $data['number'],
                'name' => $data['name'],
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal simpan kelompok', ['id' => $id, 'data' => $data, 'message' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['name' => 'Gagal menyimpan kelompok: '.$e->getMessage()]);
        }

        return redirect()->route('groups.index')->with('status', "Kelompok {$id} dibuat.");
    }

    public function destroy(string $id, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()?->isTeacher(), 403);

        try {
            foreach (collect($firebase->all('students'))->where('group_id', $id) as $student) {
                if (! empty($student['id'])) {
                    $firebase->delete('students', $student['id']);
                }
            }

            $firebase->delete('groups', $id);
        } catch (Throwable $e) {
            return back()->withErrors(['group' => 'Gagal menghapus kelompok: '.$e->getMessage()]);
        }

        return redirect()->route('groups.index')->with('status', 'Kelompok dihapus.');
    }

    public function storeStudent(Request $request, string $groupId, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()?->isTeacher(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        try {
            $firebase->push('students', [
                'name' => $data['name'],
                'group_id' => $groupId,
                'slug' => Str::slug($data['name']),
            ]);
        } catch (Throwable $e) {
            Log::error('Gagal daftarkan siswa', ['group_id' => $groupId, 'message' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['name' => 'Gagal mendaftarkan siswa: '.$e->getMessage()]);
        }

        return redirect()->route('groups.index')->with('status', 'Siswa didaftarkan.');
    }

    public function destroyStudent(string $studentId, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(current_user()?->isTeacher(), 403);

        try {
            $firebase->delete('students', $studentId);
        } catch (Throwable $e) {
            return back()->withErrors(['student' => 'Gagal menghapus siswa: '.$e->getMessage()]);
        }

        return redirect()->route('groups.index')->with('status', 'Siswa dihapus.');
    }
}
