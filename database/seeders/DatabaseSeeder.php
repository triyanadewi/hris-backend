<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            CompaniesSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            BranchesSeeder::class,
            DivisionsSeeder::class,
            PositionsSeeder::class,
            EmployeesSeeder::class,
            AchievementsSeeder::class,
            LetterFormatsTableSeeder::class,
            LettersSeeder::class,
            CheckClockSettingsTableSeeder::class,
            CheckClockSettingTimesTableSeeder::class,
            CheckClocksTableSeeder::class,
            SalariesSeeder::class,
            PackagesSeeder::class,
            PackageBenefitsSeeder::class,
            OrdersSeeder::class,
            SubscriptionsSeeder::class,
        ]);

    }
}
