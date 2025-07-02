<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\PackageBenefit;
use App\Models\Company;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packageBenefits = PackageBenefit::all();
        $companies = Company::all();

        if ($packageBenefits->isEmpty() || $companies->isEmpty()) {
            $this->command->info('Please run PackageBenefitsSeeder and CompaniesSeeder first.');
            return;
        }

        $orders = [
            [
                'company_name' => 'PT Tech Solutions Indonesia',
                'email' => 'admin@techsolutions.com',
                'phone_number' => '021-12345678',
                'add_branch' => 2,
                'add_employees' => 50,
                'duration_days' => 365,
                'subtotal' => 15000000,
                'tax' => 1500000,
                'total' => 16500000,
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'TXN-2024-001',
                'paid_at' => now()->subDays(30),
            ],
            [
                'company_name' => 'CV Digital Marketing Pro',
                'email' => 'info@digitalmarketing.com',
                'phone_number' => '021-87654321',
                'add_branch' => 1,
                'add_employees' => 25,
                'duration_days' => 180,
                'subtotal' => 8000000,
                'tax' => 800000,
                'total' => 8800000,
                'status' => 'paid',
                'payment_method' => 'credit_card',
                'payment_reference' => 'TXN-2024-002',
                'paid_at' => now()->subDays(15),
            ],
            [
                'company_name' => 'PT Retail Modern',
                'email' => 'contact@retailmodern.com',
                'phone_number' => '021-55667788',
                'add_branch' => 5,
                'add_employees' => 100,
                'duration_days' => 365,
                'subtotal' => 25000000,
                'tax' => 2500000,
                'total' => 27500000,
                'status' => 'pending',
                'payment_method' => null,
                'payment_reference' => null,
                'paid_at' => null,
            ],
            [
                'company_name' => 'PT Manufacturing Excellence',
                'email' => 'admin@manufacturing.com',
                'phone_number' => '021-99887766',
                'add_branch' => 3,
                'add_employees' => 75,
                'duration_days' => 365,
                'subtotal' => 20000000,
                'tax' => 2000000,
                'total' => 22000000,
                'status' => 'failed',
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'TXN-2024-004',
                'paid_at' => null,
            ],
            [
                'company_name' => 'CV Startup Innovation',
                'email' => 'hello@startup.com',
                'phone_number' => '021-11223344',
                'add_branch' => 0,
                'add_employees' => 10,
                'duration_days' => 90,
                'subtotal' => 3000000,
                'tax' => 300000,
                'total' => 3300000,
                'status' => 'paid',
                'payment_method' => 'e_wallet',
                'payment_reference' => 'TXN-2024-005',
                'paid_at' => now()->subDays(5),
            ]
        ];

        foreach ($orders as $orderData) {
            Order::create([
                'package_benefits_id' => $packageBenefits->random()->id,
                'company_id' => $companies->random()->id,
                'company_name' => $orderData['company_name'],
                'email' => $orderData['email'],
                'phone_number' => $orderData['phone_number'],
                'add_branch' => $orderData['add_branch'],
                'add_employees' => $orderData['add_employees'],
                'duration_days' => $orderData['duration_days'],
                'subtotal' => $orderData['subtotal'],
                'tax' => $orderData['tax'],
                'total' => $orderData['total'],
                'status' => $orderData['status'],
                'payment_method' => $orderData['payment_method'],
                'payment_reference' => $orderData['payment_reference'],
                'paid_at' => $orderData['paid_at'],
            ]);
        }

        $this->command->info('Orders seeded successfully!');
    }
}
