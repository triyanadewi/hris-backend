<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->info('Please run CompaniesSeeder first.');
            return;
        }

        // Create one admin user for each company
        foreach ($companies as $company) {
            User::create([
                'company_id' => $company->id,
                'name' => $company->name . ' Admin',
                'email' => 'admin@' . strtolower(str_replace([' ', '.', 'inc', 'cv', 'pt'], '', $company->name)) . '.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'isProfileCompany' => true,
            ]);
        }

        $this->command->info('Users seeded successfully! Created ' . $companies->count() . ' admin users.');
    }
}
