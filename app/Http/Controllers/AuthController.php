<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(FirebaseService $firebase): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $groups = collect($firebase->all('groups'))
            ->sortBy(fn ($group) => (int) ($group['number'] ?? 0))
            ->values()
            ->all();

        $students = User::where('role', 'siswa')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'name', 'group_id'])
            ->groupBy('group_id')
            ->map(fn ($items) => $items->values()->all())
            ->all();

        return view('auth.login', [
            'groups' => $groups,
            'studentsByGroup' => $students,
        ]);
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

    public function studentLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'siswa')],
        ]);

        Auth::loginUsingId((int) $data['student_id']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
