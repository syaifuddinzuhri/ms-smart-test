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
            'email' => 'admin@mssmart.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN->value,
        ]);

        User::create([
            'name' => 'Guru Pengajar',
            'username' => 'teacher',
            'email' => 'teacher@mssmart.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TEACHER->value,
        ]);

        User::create([
            'name' => 'Pengawas 1',
            'username' => 'supervisor',
            'email' => 'supervisor@mssmart.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPERVISOR->value,
        ]);
    }
}
