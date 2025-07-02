<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Salary;
use App\Models\User;

class SalariesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'employee')->get();

        if ($users->isEmpty()) {
            $this->command->info('Please run UserSeeder and EmployeesSeeder first.');
            return;
        }

        $salaries = [
            [
                'type' => 1, // 1 = monthly, 2 = hourly, 3 = daily
                'rate' => 8000000,
                'effective_date' => '2024-01-01',
                'status' => 'active',
            ],
            [
                'type' => 1,
                'rate' => 12000000,
                'effective_date' => '2024-01-01',
                'status' => 'active',
            ],
            [
                'type' => 1,
                'rate' => 15000000,
                'effective_date' => '2024-01-01',
                'status' => 'active',
            ],
            [
                'type' => 2, // hourly
                'rate' => 75000,
                'effective_date' => '2024-02-01',
                'status' => 'active',
            ],
            [
                'type' => 1,
                'rate' => 10000000,
                'effective_date' => '2024-03-01',
                'status' => 'active',
            ],
        ];

        foreach ($salaries as $index => $salaryData) {
            if (isset($users[$index])) {
                Salary::create([
                    'user_id' => $users[$index]->id,
                    'type' => $salaryData['type'],
                    'rate' => $salaryData['rate'],
                    'effective_date' => $salaryData['effective_date'],
                    'status' => $salaryData['status'],
                ]);
            }
        }

        // Create additional random salaries for remaining users
        foreach ($users->skip(5) as $user) {
            Salary::create([
                'user_id' => $user->id,
                'type' => rand(1, 3),
                'rate' => rand(5000000, 20000000),
                'effective_date' => now()->subMonths(rand(1, 12)),
                'status' => 'active',
            ]);
        }

        $this->command->info('Salaries seeded successfully!');
    }
}
