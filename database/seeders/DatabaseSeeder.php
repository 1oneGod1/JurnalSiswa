<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'guru@example.com'],
            [
                'name' => 'Guru STEM',
                'password' => Hash::make('password'),
                'role' => 'guru',
                'group_id' => null,
            ],
        );
    }
}
