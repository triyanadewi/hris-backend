<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Letters;
use App\Models\Letter_formats;
use App\Models\User;

class LettersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $letterFormats = Letter_formats::all();
        $users = User::where('role', 'employee')->get();

        if ($letterFormats->isEmpty() || $users->isEmpty()) {
            $this->command->info('Please run LetterFormatsTableSeeder and UserSeeder first.');
            return;
        }

        $letters = [
            [
                'resignation_date' => '2024-12-31',
                'reason_resign' => 'Personal career development and new opportunities',
                'additional_notes' => 'Thank you for the great experience working here.',
                'current_division' => null,
                'requested_division' => null,
                'reason_transfer' => null,
                'current_salary' => null,
                'requested_salary' => null,
                'reason_salary' => null,
                'leave_start' => null,
                'return_to_work' => null,
                'reason_for_leave' => null,
                'is_sent' => true,
                'is_approval' => false,
            ],
            [
                'resignation_date' => null,
                'reason_resign' => null,
                'additional_notes' => 'Request for division transfer to better utilize my skills.',
                'current_division' => 'IT Department',
                'requested_division' => 'Finance Department',
                'reason_transfer' => 'Better career prospects and skill utilization',
                'current_salary' => null,
                'requested_salary' => null,
                'reason_salary' => null,
                'leave_start' => null,
                'return_to_work' => null,
                'reason_for_leave' => null,
                'is_sent' => true,
                'is_approval' => true,
            ],
            [
                'resignation_date' => null,
                'reason_resign' => null,
                'additional_notes' => 'Request for salary adjustment based on performance.',
                'current_division' => null,
                'requested_division' => null,
                'reason_transfer' => null,
                'current_salary' => 8000000,
                'requested_salary' => 10000000,
                'reason_salary' => 'Increased responsibilities and excellent performance review',
                'leave_start' => null,
                'return_to_work' => null,
                'reason_for_leave' => null,
                'is_sent' => true,
                'is_approval' => false,
            ],
            [
                'resignation_date' => null,
                'reason_resign' => null,
                'additional_notes' => 'Request for annual leave for family vacation.',
                'current_division' => null,
                'requested_division' => null,
                'reason_transfer' => null,
                'current_salary' => null,
                'requested_salary' => null,
                'reason_salary' => null,
                'leave_start' => '2024-07-15',
                'return_to_work' => '2024-07-29',
                'reason_for_leave' => 'Family vacation and personal time',
                'is_sent' => true,
                'is_approval' => true,
            ],
            [
                'resignation_date' => null,
                'reason_resign' => null,
                'additional_notes' => 'Medical leave request due to health condition.',
                'current_division' => null,
                'requested_division' => null,
                'reason_transfer' => null,
                'current_salary' => null,
                'requested_salary' => null,
                'reason_salary' => null,
                'leave_start' => '2024-06-01',
                'return_to_work' => '2024-06-15',
                'reason_for_leave' => 'Medical treatment and recovery',
                'is_sent' => true,
                'is_approval' => true,
            ]
        ];

        foreach ($letters as $letterData) {
            Letters::create([
                'letter_format_id' => $letterFormats->random()->id,
                'user_id' => $users->random()->id,
                'resignation_date' => $letterData['resignation_date'],
                'reason_resign' => $letterData['reason_resign'],
                'additional_notes' => $letterData['additional_notes'],
                'current_division' => $letterData['current_division'],
                'requested_division' => $letterData['requested_division'],
                'reason_transfer' => $letterData['reason_transfer'],
                'current_salary' => $letterData['current_salary'],
                'requested_salary' => $letterData['requested_salary'],
                'reason_salary' => $letterData['reason_salary'],
                'leave_start' => $letterData['leave_start'],
                'return_to_work' => $letterData['return_to_work'],
                'reason_for_leave' => $letterData['reason_for_leave'],
                'is_sent' => $letterData['is_sent'],
                'is_approval' => $letterData['is_approval'],
            ]);
        }

        $this->command->info('Letters seeded successfully!');
    }
}
