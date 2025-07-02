<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckClockSettings;
use App\Models\Company;
use App\Models\Branch;

class CheckClockSettingsTableSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::all();
        $branches = Branch::all();

        if ($companies->isEmpty()) {
            $this->command->info('Please run CompaniesSeeder first.');
            return;
        }

        // Create settings for head office (branch_id = null)
        foreach ($companies as $company) {
            CheckClockSettings::create([
                'company_id' => $company->id,
                'branch_id' => null, // Head office
                'location_name' => $company->name . ' Head Office',
                'latitude' => -6.20000000,
                'longitude' => 106.81666667,
                'radius' => 100, // radius 100 meter
            ]);
        }

        // Create settings for each branch
        foreach ($branches as $branch) {
            CheckClockSettings::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'location_name' => $branch->name,
                'latitude' => $this->getRandomLatitude(),
                'longitude' => $this->getRandomLongitude(),
                'radius' => rand(50, 200), // random radius between 50-200 meters
            ]);
        }

        $this->command->info('Check Clock Settings seeded successfully!');
    }

    private function getRandomLatitude()
    {
        // Random latitude around Jakarta area
        return -6.2 + (rand(-100, 100) / 1000);
    }

    private function getRandomLongitude()
    {
        // Random longitude around Jakarta area
        return 106.8 + (rand(-100, 100) / 1000);
    }
}
