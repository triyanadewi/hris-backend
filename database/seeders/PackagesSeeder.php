<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'id' => 1,
                'name' => 'Free Trial',
                'price' => 0,
                'description' => 'Try before you buy — full features for 2 weeks',
            ],
            [
                'id' => 2,
                'name' => 'Starter',
                'price' => 500000,
                'description' => 'Ideal for small businesses starting their journey',
            ],
            [
                'id' => 3,
                'name' => 'Growth',
                'price' => 1800000,
                'description' => 'Perfect for expanding companies with multiple locations',
            ],
            [
                'id' => 4,
                'name' => 'Pro',
                'price' => 3600000,
                'description' => 'Advanced features for mid-sized enterprises',
            ],
            [
                'id' => 5,
                'name' => 'Enterprise',
                'price' => 7200000,
                'description' => 'Comprehensive solution for large organizations',
            ],
        ];

        DB::table('packages')->insert($packages);
    }
}
