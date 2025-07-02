<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all admin users from UserSeeder
        $adminUsers = User::where('role', 'admin')->get();
        
        if ($adminUsers->isEmpty()) {
            $this->command->info('No admin users found. Please run UserSeeder first.');
            return;
        }

        // Create admin profiles for all admin users
        foreach ($adminUsers as $adminUser) {
            // Check if admin profile already exists
            $existingAdmin = Admin::where('user_id', $adminUser->id)->first();
            
            if (!$existingAdmin) {
                Admin::create([
                    'user_id' => $adminUser->id,
                    'phone_number' => $this->generatePhoneNumber(),
                    'profile_photo' => 'admin_photos/admin_' . $adminUser->id . '.jpg',
                ]);
            }
        }

        $this->command->info('Admin profiles created successfully!');
    }

    /**
     * Generate a random Indonesian phone number
     */
    private function generatePhoneNumber()
    {
        $prefixes = ['0811', '0812', '0813', '0821', '0822', '0823', '0851', '0852', '0853'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = $prefix . rand(1000000, 9999999);
        return $number;
    }
}
