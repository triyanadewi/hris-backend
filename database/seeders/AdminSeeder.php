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
        // Get existing admin user from UserSeeder
        $adminUser1 = User::where('email', 'admin@example.com')->where('role', 'admin')->first();
        
        // Create additional admin user using DB::table to avoid auto-increment conflict
        $adminUser2 = User::where('email', 'test@admin.com')->first();
        if (!$adminUser2) {
            DB::table('users')->insert([
                'name' => 'Test Admin',
                'email' => 'test@admin.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $adminUser2 = User::find(3);
        }

        // Create admin profiles for existing admin users
        if ($adminUser1) {
            $admin1 = Admin::where('user_id', $adminUser1->id)->first();
            if (!$admin1) {
                Admin::create([
                    'user_id' => $adminUser1->id,
                    'phone_number' => '081234567890',
                    'profile_photo' => null,
                ]);
            }
        }

        if ($adminUser2) {
            $admin2 = Admin::where('user_id', $adminUser2->id)->first();
            if (!$admin2) {
                Admin::create([
                    'user_id' => $adminUser2->id,
                    'phone_number' => '081234567891',
                    'profile_photo' => null,
                ]);
            }
        }
    }
}
