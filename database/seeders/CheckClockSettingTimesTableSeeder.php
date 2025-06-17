<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CheckClockSettingTimes;
use App\Models\CheckClockSettings;

class CheckClockSettingTimesTableSeeder extends Seeder
{
    public function run()
    {
        // Ambil setting WFO (asumsi id 1)
        $wfo = CheckClockSettings::where('type', 'WFO')->first();

        if ($wfo) {
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($days as $day) {
                CheckClockSettingTimes::create([
                    'ck_settings_id' => $wfo->id,
                    'day' => $day,
                    'clock_in' => '08:00:00',
                    'clock_out' => '17:00:00',
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                    'work_day' => true,
                ]);
            }

            // Sabtu dan Minggu tidak bekerja
            CheckClockSettingTimes::create([
                'ck_settings_id' => $wfo->id,
                'day' => 'Saturday',
                'clock_in' => '00:00:00',
                'clock_out' => '00:00:00',
                'work_day' => false,
            ]);
            CheckClockSettingTimes::create([
                'ck_settings_id' => $wfo->id,
                'day' => 'Sunday',
                'clock_in' => '00:00:00',
                'clock_out' => '00:00:00',
                'work_day' => false,
            ]);
        }
    }
}
