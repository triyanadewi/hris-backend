<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('companies')->insert([
            [
                'id' => 1,
                'name' => 'Tech Solutions Inc.',
                'email' => 'Tech@polinema.com',
                'head_office_phone' => '08512345678',
                'head_office_phone_backup' => '08587654321',
                'head_office_address' => 'Serayu Street No. 45, Malang, Indonesia',
                'description' => 'A leading provider of innovative technology solutions.',
            ]
        ]);
    }
}
