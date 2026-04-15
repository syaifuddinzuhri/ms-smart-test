<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@cbt.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN->value,
        ]);

        User::create([
            'name' => 'Guru Pengajar',
            'username' => 'teacher',
            'email' => 'teacher@cbt.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TEACHER->value,
        ]);
    }
}
