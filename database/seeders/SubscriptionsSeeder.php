<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subscription;
use App\Models\Company;
use App\Models\Package;

class SubscriptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $packages = Package::all();

        if ($companies->isEmpty() || $packages->isEmpty()) {
            $this->command->info('Please run CompaniesSeeder and PackagesSeeder first.');
            return;
        }

        $subscriptions = [
            [
                'extra_branch' => 2,
                'extra_employee' => 50,
                'starts_at' => now()->subDays(30),
                'ends_at' => now()->addDays(335),
                'is_active' => true,
            ],
            [
                'extra_branch' => 1,
                'extra_employee' => 25,
                'starts_at' => now()->subDays(15),
                'ends_at' => now()->addDays(165),
                'is_active' => true,
            ],
            [
                'extra_branch' => 5,
                'extra_employee' => 100,
                'starts_at' => now()->subDays(60),
                'ends_at' => now()->addDays(305),
                'is_active' => true,
            ],
            [
                'extra_branch' => 0,
                'extra_employee' => 10,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(85),
                'is_active' => true,
            ],
            [
                'extra_branch' => 3,
                'extra_employee' => 75,
                'starts_at' => now()->subDays(90),
                'ends_at' => now()->subDays(10),
                'is_active' => false, // Expired subscription
            ]
        ];

        foreach ($subscriptions as $subscriptionData) {
            Subscription::create([
                'company_id' => $companies->random()->id,
                'package_id' => $packages->random()->id,
                'extra_branch' => $subscriptionData['extra_branch'],
                'extra_employee' => $subscriptionData['extra_employee'],
                'starts_at' => $subscriptionData['starts_at'],
                'ends_at' => $subscriptionData['ends_at'],
                'is_active' => $subscriptionData['is_active'],
            ]);
        }

        $this->command->info('Subscriptions seeded successfully!');
    }
}
