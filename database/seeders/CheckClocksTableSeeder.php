<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\CheckClocks;
use App\Models\CheckClockSettings;
use App\Models\Branch;
use Carbon\Carbon;

class CheckClocksTableSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::all();
        $checkClockSettings = CheckClockSettings::all();
        $branches = Branch::all();

        if ($employees->isEmpty() || $checkClockSettings->isEmpty()) {
            $this->command->info('Please run EmployeesSeeder and CheckClockSettingsTableSeeder first.');
            return;
        }

        // Create check clock records for the last 7 days
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = Carbon::now()->subDays($i);
        }

        foreach ($employees as $employee) {
            $setting = $checkClockSettings->random();
            $branch = $branches->where('id', $employee->Branch_id)->first();

            foreach ($dates as $date) {
                $dateString = $date->toDateString();
                $dayName = $date->format('l'); // Monday, Tuesday, etc.

                // Skip weekends (simulate not working on weekends)
                if (in_array($dayName, ['Saturday', 'Sunday'])) {
                    continue;
                }

                // Randomly simulate some employees not coming to work
                if (rand(1, 10) > 8) { // 20% chance of not coming
                    CheckClocks::create([
                        'employee_id' => $employee->id,
                        'ck_settings_id' => $setting->id,
                        'branch_id' => $branch ? $branch->id : null,
                        'check_clock_type' => 'absent',
                        'check_clock_date' => $dateString,
                        'check_clock_time' => '00:00:00',
                        'status' => 'Absent',
                        'approved' => true,
                        'location' => null,
                        'address' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'photo' => null
                    ]);
                    continue;
                }

                // Create check-in record
                $checkInTime = $this->getRandomCheckInTime();
                $status = $checkInTime <= '08:00:00' ? 'On Time' : 'Late';

                CheckClocks::create([
                    'employee_id' => $employee->id,
                    'ck_settings_id' => $setting->id,
                    'branch_id' => $branch ? $branch->id : null,
                    'check_clock_type' => 'check-in',
                    'check_clock_date' => $dateString,
                    'check_clock_time' => $checkInTime,
                    'status' => $status,
                    'approved' => true,
                    'location' => $setting->location_name,
                    'address' => $branch ? $branch->branch_address : $setting->location_name,
                    'latitude' => $setting->latitude,
                    'longitude' => $setting->longitude,
                    'photo' => 'checkin_' . $employee->id . '_' . $dateString . '.jpg'
                ]);

                // Create check-out record
                $checkOutTime = $this->getRandomCheckOutTime();
                CheckClocks::create([
                    'employee_id' => $employee->id,
                    'ck_settings_id' => $setting->id,
                    'branch_id' => $branch ? $branch->id : null,
                    'check_clock_type' => 'check-out',
                    'check_clock_date' => $dateString,
                    'check_clock_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'status' => $status,
                    'approved' => true,
                    'location' => $setting->location_name,
                    'address' => $branch ? $branch->branch_address : $setting->location_name,
                    'latitude' => $setting->latitude,
                    'longitude' => $setting->longitude,
                    'photo' => 'checkout_' . $employee->id . '_' . $dateString . '.jpg'
                ]);
            }
        }

        $this->command->info('Check Clocks seeded successfully!');
    }

    private function getRandomCheckInTime()
    {
        // Random check-in time between 07:30 and 09:00
        $minutes = rand(450, 540); // 7:30 AM = 450 minutes, 9:00 AM = 540 minutes
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d:00', $hours, $mins);
    }

    private function getRandomCheckOutTime()
    {
        // Random check-out time between 17:00 and 18:30
        $minutes = rand(1020, 1110); // 5:00 PM = 1020 minutes, 6:30 PM = 1110 minutes
        $hours = intval($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d:00', $hours, $mins);
    }
}
