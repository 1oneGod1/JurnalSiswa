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
