<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use App\Models\CheckClocks;
use Carbon\Carbon;

class CheckClocksTableSeeder extends Seeder
{
    public function run()
    {
        // Daftar user sample (bisa ditambah sebanyak yang diinginkan)
        $userSamples = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'NIK' => '12345'],
            ['name' => 'Steven Wong', 'email' => 'steven@example.com', 'NIK' => '67890'],
            ['name' => 'Lisa Putri', 'email' => 'lisa@example.com', 'NIK' => '11111'],
            ['name' => 'Dian Permana', 'email' => 'dian@example.com', 'NIK' => '22222'],
            ['name' => 'Rudi Hartono', 'email' => 'rudi@example.com', 'NIK' => '33333'],
        ];

        foreach ($userSamples as $sample) {
            // Buat user
            $user = User::firstOrCreate(
                ['email' => $sample['email']],
                [
                    'name' => $sample['name'],
                    'password' => bcrypt('password'),
                    'role' => 'employee'
                ]
            );

            // Buat employee
            $employee = Employee::firstOrCreate(
                ['NIK' => $sample['NIK']],
                [
                    'user_id' => $user->id,
                    'FirstName' => explode(' ', $sample['name'])[0],
                    'LastName' => explode(' ', $sample['name'])[1] ?? '',
                    'Gender' => 'Male',
                    'Address' => 'Jl. Veteran No. 1',
                    'PhoneNumber' => '08123456789',
                    'Branch' => 'Main Office',
                    'Position' => 'Staff IT',
                    'Division' => 'IT',
                    'Status' => 'Active',
                    'LastEducation' => 'S1',
                    'PlaceOfBirth' => 'Malang',
                    'BirthDate' => '1990-01-01',
                    'ContractType' => 'Permanent',
                    'Bank' => 'BCA',
                    'BankAccountNumber' => '1234567890',
                    'BankAccountHolderName' => $sample['name'],
                    'photo' => 'default.jpg'
                ]
            );

            // Buat data check clock 3 hari terakhir
            $dates = [
                Carbon::now()->subDays(2),
                Carbon::now()->subDays(1),
                Carbon::now(),
            ];

            foreach ($dates as $date) {
                $dateString = $date->toDateString();

                // Check jika belum ada record untuk hari itu
                $exists = CheckClocks::where('employee_id', $employee->id)
                    ->where('check_clock_date', $dateString)
                    ->count();

                if ($exists == 0) {
                    // Check-in
                    CheckClocks::create([
                        'employee_id' => $employee->id,
                        'check_clock_type' => 'check-in',
                        'check_clock_date' => $dateString,
                        'check_clock_time' => '08:00:00',
                        'status' => 'On Time',
                        'approved' => true,
                        'location' => 'Office',
                        'address' => 'Jl. Veteran No. 1, Malang',
                        'latitude' => -7.983908,
                        'longitude' => 112.621381,
                        'photo' => 'checkin.jpg'
                    ]);

                    // Check-out
                    CheckClocks::create([
                        'employee_id' => $employee->id,
                        'check_clock_type' => 'check-out',
                        'check_clock_date' => $dateString,
                        'check_clock_time' => '17:00:00',
                        'status' => 'On Time',
                        'approved' => true,
                        'location' => 'Office',
                        'address' => 'Jl. Veteran No. 1, Malang',
                        'latitude' => -7.983908,
                        'longitude' => 112.621381,
                        'photo' => 'checkout.jpg'
                    ]);
                }
            }
        }
    }
}
