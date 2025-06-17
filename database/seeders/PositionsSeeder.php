<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('positions')->insert([
            [
                'id' => 1,
                'division_id' => 1,
                'name' => 'Staff',
                'description' => 'Carries out daily operational tasks and supports the execution of team objectives.',
            ],
            [
                'id' => 2,
                'division_id' => 1,
                'name' => 'Head of Divisions',
                'description' => 'Leads and manages a division, ensuring alignment with company goals and efficient team performance.',
            ],
            [
                'id' => 3,
                'division_id' => 2,
                'name' => 'Supervisor',
                'description' => 'Supervises staff activities, ensures workflow efficiency, and acts as a liaison between staff and management.',
            ],
            [
                'id' => 4,
                'division_id' => 3,
                'name' => 'Marketing Executive',
                'description' => 'Executes marketing strategies and campaigns to boost sales.',
            ],
            [
                'id' => 5,
                'division_id' => 4,
                'name' => 'Sales Representative',
                'description' => 'Manages client relations and closes deals.',
            ],
            [
                'id' => 6,
                'division_id' => 5,
                'name' => 'IT Support Technician',
                'description' => 'Troubleshoots IT issues and maintains system operations.',
            ],
            [
                'id' => 7,
                'division_id' => 6,
                'name' => 'R&D Specialist',
                'description' => 'Develops new products and tests innovations.',
            ],
            [
                'id' => 8,
                'division_id' => 7,
                'name' => 'Customer Service Agent',
                'description' => 'Provides assistance and resolves client issues.',
            ],
            [
                'id' => 9,
                'division_id' => 9,
                'name' => 'QA Inspector',
                'description' => 'Performs inspections to ensure quality compliance.',
            ],
        ]);
    }
}