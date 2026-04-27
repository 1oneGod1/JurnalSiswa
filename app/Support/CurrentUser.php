<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Unified user object yang merepresentasikan guru (Laravel Auth / SQLite)
 * atau siswa (Firebase Realtime via session).
 */
class CurrentUser
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $role,
        public readonly ?string $group_id,
    ) {
    }

    public function isTeacher(): bool
    {
        return $this->role === 'guru';
    }

    public function isStudent(): bool
    {
        return $this->role === 'siswa';
    }

    public static function resolve(): ?self
    {
        if (Auth::check()) {
            $user = Auth::user();

            return new self(
                id: (string) $user->id,
                name: (string) $user->name,
                role: (string) ($user->role ?? 'guru'),
                group_id: $user->group_id ?? null,
            );
        }

        if ($studentId = Session::get('student_id')) {
            return new self(
                id: (string) $studentId,
                name: (string) Session::get('student_name', 'Siswa'),
                role: 'siswa',
                group_id: Session::get('student_group_id'),
            );
        }

        return null;
    }
}
