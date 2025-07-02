<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Division;
use App\Models\Branch;

class DivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        
        if ($branches->isEmpty()) {
            $this->command->info('Please run BranchesSeeder first.');
            return;
        }

        $divisions = [
            ['name' => 'Human Resources', 'description' => 'Responsible for managing employee relations and benefits.'],
            ['name' => 'Finance', 'description' => 'Handles financial planning and record-keeping.'],
            ['name' => 'Marketing', 'description' => 'Focuses on promoting the company\'s products and services.'],
            ['name' => 'Sales', 'description' => 'Responsible for selling the company\'s products and services.'],
            ['name' => 'IT Support', 'description' => 'Provides technical support and manages IT infrastructure.'],
            ['name' => 'Research and Development', 'description' => 'Conducts research to innovate and improve products.'],
            ['name' => 'Customer Service', 'description' => 'Handles customer inquiries and support.'],
            ['name' => 'Operations', 'description' => 'Manages day-to-day business operations.'],
        ];

        // Create divisions for each branch
        foreach ($branches as $branch) {
            foreach ($divisions as $divisionData) {
                Division::create([
                    'branch_id' => $branch->id,
                    'name' => $divisionData['name'],
                    'description' => $divisionData['description'],
                ]);
            }
        }

        $this->command->info('Divisions seeded successfully!');
    }
}
