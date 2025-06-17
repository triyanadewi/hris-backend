<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageBenefitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benefits = [
            [
                'package_id' => 1,
                'max_branches' => 0,
                'max_employees' => 25,
                'access_duration_days' => 14,
                'is_active' => true,
            ],
            [
                'package_id' => 2,
                'max_branches' => 0,
                'max_employees' => 50,
                'access_duration_days' => 30,
                'is_active' => true,
            ],
            [
                'package_id' => 3,
                'max_branches' => 2,
                'max_employees' => 250,
                'access_duration_days' => 90,
                'is_active' => true,
            ],
            [
                'package_id' => 4,
                'max_branches' => 3,
                'max_employees' => 500,
                'access_duration_days' => 180,
                'is_active' => true,
            ],
            [
                'package_id' => 5,
                'max_branches' => 4,
                'max_employees' => 1000,
                'access_duration_days' => 365,
                'is_active' => true,
            ],
        ];

        DB::table('package_benefits')->insert($benefits);
    }
}
