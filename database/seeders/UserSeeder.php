<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'isProfileCompany' => false,
            ],
            [
                'name' => 'Employee One',
                'email' => 'employee1@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'isProfileCompany' => true,
            ],
            [
                'name' => 'Employee Two',
                'email' => 'employee2@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employee',
                'isProfileCompany' => true,
            ],
        ];
        foreach ($users as $user) {
            User::create($user);
        }
    }
}
