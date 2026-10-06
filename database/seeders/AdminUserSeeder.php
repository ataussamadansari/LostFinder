<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(AdminRoleSeeder::class);

        // Create or update default Super Admin
        $user = User::firstOrCreate(
            ['email' => 'admin@lostfinder.com'],
            [
                'name' => 'Super Administrator',
                'phone' => '+919999999999',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Ensure password is admin123 and status is active
        $user->update([
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create AdminUser record
        $adminUser = AdminUser::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active']
        );

        $adminUser->update(['status' => 'active']);

        // Assign super_admin role
        $adminUser->assignRole('super_admin');
    }
}
