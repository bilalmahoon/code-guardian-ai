<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Organization\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
        ]);

        // Create super admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@codeguardian.ai'],
            [
                'name'              => 'Super Admin',
                'email'             => 'admin@codeguardian.ai',
                'password'          => Hash::make('Admin@123456'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');

        // Create demo developer
        $demo = User::updateOrCreate(
            ['email' => 'demo@codeguardian.ai'],
            [
                'name'              => 'Demo User',
                'email'             => 'demo@codeguardian.ai',
                'password'          => Hash::make('Demo@123456'),
                'email_verified_at' => now(),
            ]
        );

        $demo->assignRole('developer');
    }
}
