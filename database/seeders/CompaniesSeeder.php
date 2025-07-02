<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Tech Solutions Inc.',
                'email' => 'info@techsolutions.com',
                'head_office_phone' => '021-12345678',
                'head_office_phone_backup' => '021-87654321',
                'head_office_address' => 'Jl. Sudirman No. 123, Jakarta Pusat',
                'description' => 'A leading provider of innovative technology solutions.',
            ],
            [
                'name' => 'Digital Marketing Pro',
                'email' => 'contact@digitalmarketing.com',
                'head_office_phone' => '021-11223344',
                'head_office_phone_backup' => '021-44332211',
                'head_office_address' => 'Jl. Thamrin No. 456, Jakarta Pusat',
                'description' => 'Professional digital marketing and advertising agency.',
            ],
            [
                'name' => 'Retail Modern',
                'email' => 'admin@retailmodern.com',
                'head_office_phone' => '021-55667788',
                'head_office_phone_backup' => '021-88776655',
                'head_office_address' => 'Jl. Gatot Subroto No. 789, Jakarta Selatan',
                'description' => 'Modern retail solutions and services.',
            ]
        ];

        foreach ($companies as $companyData) {
            Company::create($companyData);
        }

        $this->command->info('Companies seeded successfully!');
    }
}
