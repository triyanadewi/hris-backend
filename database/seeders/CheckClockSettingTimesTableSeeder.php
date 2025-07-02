<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckClockSettingTimes;
use App\Models\CheckClockSettings;

class CheckClockSettingTimesTableSeeder extends Seeder
{
    public function run()
    {
        $checkClockSettings = CheckClockSettings::all();

        if ($checkClockSettings->isEmpty()) {
            $this->command->info('Please run CheckClockSettingsTableSeeder first.');
            return;
        }

        // Create time settings for each location
        foreach ($checkClockSettings as $setting) {
            // Work days Monday to Friday
            $workDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($workDays as $day) {
                CheckClockSettingTimes::create([
                    'ck_settings_id' => $setting->id,
                    'day' => $day,
                    'clock_in_start' => '07:00:00',
                    'clock_in_end' => '09:00:00',
                    'clock_in_on_time_limit' => '08:00:00',
                    'clock_out_start' => '17:00:00',
                    'clock_out_end' => '19:00:00',
                    'work_day' => true,
                ]);
            }

            // Weekend - Saturday
            CheckClockSettingTimes::create([
                'ck_settings_id' => $setting->id,
                'day' => 'Saturday',
                'clock_in_start' => '08:00:00',
                'clock_in_end' => '10:00:00',
                'clock_in_on_time_limit' => '09:00:00',
                'clock_out_start' => '12:00:00',
                'clock_out_end' => '14:00:00',
                'work_day' => false, // Optional work day
            ]);

            // Weekend - Sunday
            CheckClockSettingTimes::create([
                'ck_settings_id' => $setting->id,
                'day' => 'Sunday',
                'clock_in_start' => '00:00:00',
                'clock_in_end' => '00:00:00',
                'clock_in_on_time_limit' => '00:00:00',
                'clock_out_start' => '00:00:00',
                'clock_out_end' => '00:00:00',
                'work_day' => false, // No work on Sunday
            ]);
        }

        $this->command->info('Check Clock Setting Times seeded successfully!');
    }
}
