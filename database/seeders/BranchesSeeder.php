<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('branches')->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'name' => 'Bekasi 1',
                'branch_address' => 'Mawar Raya Street No. 123, Bekasi, West Java',
                'branch_phone' => '081234567891',
                'branch_phone_backup' => '081234567890',
            ],
            [
                'id' => 2,
                'company_id' => 1,
                'name' => 'Surabaya 1',
                'branch_address' => 'Patimura Street No. 456, Surabaya, East Java',
                'branch_phone' => '081234567892',
                'branch_phone_backup' => '081234567894',
            ],
            [
                'id' => 3,
                'company_id' => 1,
                'name' => 'Surabaya 2',
                'branch_address' => 'Sukamaju Street No. 789, Surabaya, East Java',
                'branch_phone' => '081234567893',
                'branch_phone_backup' => '081234567895',
            ],
            [
                'id' => 4,
                'company_id' => 1,
                'name' => 'Makassar 1',
                'branch_address' => 'Cempaka Street No. 101, Makassar, South Sulawesi',
                'branch_phone' => '081234567896',
                'branch_phone_backup' => '081234567897',
            ]
        ]);
    }
}
