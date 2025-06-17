<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckClockSettings;

class CheckClockSettingsTableSeeder extends Seeder
{
    public function run()
    {
        CheckClockSettings::create([
            'name' => 'Office Jakarta',
            'type' => 'WFO',
            'latitude' => -6.20000000,
            'longitude' => 106.81666667,
            'radius' => 100, // radius 100 meter
        ]);

        CheckClockSettings::create([
            'name' => 'Remote Work',
            'type' => 'WFA',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);

        CheckClockSettings::create([
            'name' => 'Hybrid Work',
            'type' => 'Hybrid',
            'latitude' => -6.21000000,
            'longitude' => 106.82000000,
            'radius' => 150,
        ]);
    }
}
