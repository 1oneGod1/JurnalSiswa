<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (current_user_check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.choose');
    }

    public function showStudentLogin(FirebaseService $firebase): View|RedirectResponse
    {
        if (current_user_check()) {
            return redirect()->route('dashboard');
        }

        $groups = collect($firebase->all('groups'))
            ->sortBy(fn ($group) => (int) ($group['number'] ?? 0))
            ->values()
            ->all();

        $students = collect($firebase->all('students'))
            ->sortBy('name')
            ->groupBy('group_id')
            ->map(fn ($items) => $items->values()->all())
            ->all();

        return view('auth.student', [
            'groups' => $groups,
            'studentsByGroup' => $students,
        ]);
    }

    public function showTeacherLogin(): View|RedirectResponse
    {
        if (current_user_check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.teacher');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password belum cocok.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function studentLogin(Request $request, FirebaseService $firebase): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
        ]);

        $student = $firebase->find('students', $data['student_id']);

        if (! $student) {
            return back()->withErrors(['student_id' => 'Siswa tidak ditemukan.']);
        }

        Session::put('student_id', $student['id']);
        Session::put('student_name', $student['name'] ?? 'Siswa');
        Session::put('student_group_id', $student['group_id'] ?? null);

        $request->session()->regenerate();
        Session::put('student_id', $student['id']);
        Session::put('student_name', $student['name'] ?? 'Siswa');
        Session::put('student_group_id', $student['group_id'] ?? null);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
        }

        Session::forget(['student_id', 'student_name', 'student_group_id']);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
