<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'description' => 'Full administrative access across platform'],
            ['name' => 'admin', 'description' => 'Standard platform administrator'],
            ['name' => 'support', 'description' => 'Customer support & ticket handling operator'],
            ['name' => 'verification_team', 'description' => 'Driver and vehicle KYC verification team'],
        ];

        foreach ($roles as $role) {
            AdminRole::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
