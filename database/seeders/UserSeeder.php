<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => UserRole::Admin],
            ['name' => 'Student One', 'email' => 'student@example.com', 'role' => UserRole::Student],
            ['name' => 'Student Two', 'email' => 'student2@example.com', 'role' => UserRole::Student],
        ];

        foreach ($users as $data) {
            if (User::where('email', $data['email'])->exists()) {
                continue;
            }

            // forceFill: `role` is not mass assignable on purpose.
            (new User)->forceFill([
                ...$data,
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
