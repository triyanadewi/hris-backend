<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Achievement;
use App\Models\Employee;

class AchievementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->info('Please run EmployeesSeeder first.');
            return;
        }

        $achievements = [
            [
                'file_path' => 'achievements/employee_of_the_month_2024.pdf',
                'original_filename' => 'Employee of the Month Certificate - January 2024.pdf',
            ],
            [
                'file_path' => 'achievements/training_certificate_leadership.pdf',
                'original_filename' => 'Leadership Training Certificate.pdf',
            ],
            [
                'file_path' => 'achievements/project_completion_award.pdf',
                'original_filename' => 'Project Excellence Award 2024.pdf',
            ],
            [
                'file_path' => 'achievements/sales_target_achievement.pdf',
                'original_filename' => 'Sales Achievement Certificate Q1 2024.pdf',
            ],
            [
                'file_path' => 'achievements/innovation_award.pdf',
                'original_filename' => 'Innovation Excellence Award 2024.pdf',
            ],
            [
                'file_path' => 'achievements/customer_service_excellence.pdf',
                'original_filename' => 'Customer Service Excellence Award.pdf',
            ],
            [
                'file_path' => 'achievements/professional_certification.pdf',
                'original_filename' => 'Professional Certification in IT.pdf',
            ],
            [
                'file_path' => 'achievements/team_collaboration_award.pdf',
                'original_filename' => 'Team Collaboration Excellence Award.pdf',
            ]
        ];

        // Assign achievements to random employees
        foreach ($achievements as $achievementData) {
            Achievement::create([
                'employee_id' => $employees->random()->id,
                'file_path' => $achievementData['file_path'],
                'original_filename' => $achievementData['original_filename'],
            ]);
        }

        // Create additional random achievements
        for ($i = 0; $i < 10; $i++) {
            Achievement::create([
                'employee_id' => $employees->random()->id,
                'file_path' => 'achievements/achievement_' . ($i + 9) . '.pdf',
                'original_filename' => 'Achievement Certificate ' . ($i + 9) . '.pdf',
            ]);
        }

        $this->command->info('Achievements seeded successfully!');
    }
}
