<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PackagePlan;

class PackagePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'title' => 'Free Trial',
                'subtitle' => 'Try before you buy — full features for 2 weeks',
                'features' => json_encode(["14 days access", "Up to 25 employees", "Head Office only"]),
                'price' => 0
            ],
            [
                'title' => 'Starter',
                'subtitle' => 'Ideal for small businesses starting their journey',
                'features' => json_encode(["1 month access", "Up to 50 employees", "Head Office only"]),
                'price' => 500000
            ],
            [
                'title' => 'Growth',
                'subtitle' => 'Perfect for expanding companies with multiple locations',
                'features' => json_encode(["3 month access", "Up to 250 employees", "Head Office with 2 branch offices"]),
                'price' => 1800000
            ],
            [
                'title' => 'Pro',
                'subtitle' => 'Advanced features for mid-sized enterprises',
                'features' => json_encode(["6 month access", "Up to 500 employees", "Head Office with 3 branch offices"]),
                'price' => 3600000
            ],
            [
                'title' => 'Enterprise',
                'subtitle' => 'Comprehensive solution for large organizations',
                'features' => json_encode(["12 month access", "Up to 1000 employees", "Head Office with 4 branch offices"]),
                'price' => 7200000
            ],
        ];

        foreach ($plans as $plan) {
            PackagePlan::create($plan);
        }
    }
}

