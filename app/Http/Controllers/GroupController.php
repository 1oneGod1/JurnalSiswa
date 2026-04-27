<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        abort_unless(auth()->user()?->isTeacher(), 403);

        $groups = collect($firebase->all('groups'))
            ->sortBy(fn ($group) => (int) ($group['number'] ?? 0))
            ->values();

        $students = User::where('role', 'siswa')
            ->orderBy('name')
            ->get()
            ->groupBy('group_id');

        return view('groups.index', [
            'groups' => $groups,
            'studentsByGroup' => $students,
        ]);
    }

    public function store(Request $request, FirebaseService $firebase): RedirectResponse
    {
        abort_unless(auth()->user()?->isTeacher(), 403);

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
        abort_unless(auth()->user()?->isTeacher(), 403);

        try {
            $firebase->delete('groups', $id);
        } catch (Throwable $e) {
            return back()->withErrors(['group' => 'Gagal menghapus kelompok: '.$e->getMessage()]);
        }

        return redirect()->route('groups.index')->with('status', 'Kelompok dihapus.');
    }

    public function storeStudent(Request $request, string $groupId): RedirectResponse
    {
        abort_unless(auth()->user()?->isTeacher(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $email = Str::slug($data['name'], '.').'-'.Str::lower(Str::random(4)).'@siswa.local';

        User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => bcrypt(Str::random(20)),
            'role' => 'siswa',
            'group_id' => $groupId,
        ]);

        return redirect()->route('groups.index')->with('status', 'Siswa didaftarkan.');
    }

    public function destroyStudent(int $studentId): RedirectResponse
    {
        abort_unless(auth()->user()?->isTeacher(), 403);

        User::where('role', 'siswa')->where('id', $studentId)->delete();

        return redirect()->route('groups.index')->with('status', 'Siswa dihapus.');
    }
}
