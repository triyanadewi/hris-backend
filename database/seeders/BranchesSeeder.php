<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Company;

class BranchesSeeder extends Seeder
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

        $branches = [
            [
                'name' => 'Jakarta Head Office',
                'branch_address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'branch_phone' => '021-12345678',
                'branch_phone_backup' => '021-12345679',
                'description' => 'Main headquarters in Jakarta',
            ],
            [
                'name' => 'Surabaya Branch',
                'branch_address' => 'Jl. Pemuda No. 456, Surabaya',
                'branch_phone' => '031-87654321',
                'branch_phone_backup' => '031-87654322',
                'description' => 'Branch office in Surabaya',
            ],
            [
                'name' => 'Bandung Branch',
                'branch_address' => 'Jl. Asia Afrika No. 789, Bandung',
                'branch_phone' => '022-11223344',
                'branch_phone_backup' => '022-11223345',
                'description' => 'Branch office in Bandung',
            ],
            [
                'name' => 'Medan Branch',
                'branch_address' => 'Jl. Merdeka No. 321, Medan',
                'branch_phone' => '061-55667788',
                'branch_phone_backup' => '061-55667789',
                'description' => 'Branch office in Medan',
            ]
        ];

        foreach ($branches as $branchData) {
            Branch::create(array_merge($branchData, [
                'company_id' => $companies->random()->id
            ]));
        }

        $this->command->info('Branches seeded successfully!');
    }
}
