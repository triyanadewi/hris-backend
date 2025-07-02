<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Division;

class PositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = Division::all();
        
        if ($divisions->isEmpty()) {
            $this->command->info('Please run DivisionsSeeder first.');
            return;
        }

        $positions = [
            ['name' => 'Staff', 'description' => 'Carries out daily operational tasks and supports the execution of team objectives.'],
            ['name' => 'Senior Staff', 'description' => 'Experienced staff member with additional responsibilities and expertise.'],
            ['name' => 'Supervisor', 'description' => 'Supervises staff activities, ensures workflow efficiency, and acts as a liaison between staff and management.'],
            ['name' => 'Team Lead', 'description' => 'Leads a small team and coordinates daily activities.'],
            ['name' => 'Manager', 'description' => 'Manages department operations and team performance.'],
            ['name' => 'Head of Division', 'description' => 'Leads and manages a division, ensuring alignment with company goals and efficient team performance.'],
        ];

        // Create positions for each division
        foreach ($divisions as $division) {
            foreach ($positions as $positionData) {
                Position::create([
                    'division_id' => $division->id,
                    'name' => $positionData['name'],
                    'description' => $positionData['description'],
                ]);
            }
        }

        $this->command->info('Positions seeded successfully!');
    }
}