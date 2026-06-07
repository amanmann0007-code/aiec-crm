<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@test.com', 'role' => 'admin'],
            ['name' => 'Reception Desk', 'email' => 'reception@test.com', 'role' => 'receptionist'],
            ['name' => 'Rahul Counselor', 'email' => 'counselor@test.com', 'role' => 'counselor'],
            ['name' => 'Tele Caller', 'email' => 'telecaller@test.com', 'role' => 'telecaller'],
            ['name' => 'Director Office', 'email' => 'director@test.com', 'role' => 'director'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => $user['role'],
                    'status' => 'active',
                ]
            );
        }
    }
}
