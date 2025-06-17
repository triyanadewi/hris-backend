<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        DB::table('users')->insert([
        [   
            'id' => 1,
            'name' => 'Admin',
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ],
        [   
            'id' => 2,
            'name' => 'Employee',
            'role' => 'employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
        ]
    ]);
    }
}
