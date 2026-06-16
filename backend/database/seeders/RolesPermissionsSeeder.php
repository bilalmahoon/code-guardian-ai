<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    private array $permissions = [
        // Organization
        'organizations.view',
        'organizations.create',
        'organizations.update',
        'organizations.delete',

        // Members
        'members.view',
        'members.invite',
        'members.remove',

        // Repositories
        'repositories.view',
        'repositories.connect',
        'repositories.disconnect',
        'repositories.sync',

        // Projects
        'projects.view',
        'projects.create',
        'projects.update',
        'projects.delete',

        // Analyses
        'analyses.view',
        'analyses.trigger',
        'analyses.cancel',
        'analyses.view_details',

        // Issues
        'issues.view',
        'issues.dismiss',

        // Reports
        'reports.view',
        'reports.generate',
        'reports.download',

        // Settings
        'settings.view',
        'settings.update',

        // Subscriptions
        'subscriptions.view',
        'subscriptions.manage',

        // Super admin only
        'platform.access',
    ];

    private array $roles = [
        'super_admin'  => 'all',
        'org_admin'    => [
            'organizations.view', 'organizations.update',
            'members.view', 'members.invite', 'members.remove',
            'repositories.view', 'repositories.connect', 'repositories.disconnect', 'repositories.sync',
            'projects.view', 'projects.create', 'projects.update', 'projects.delete',
            'analyses.view', 'analyses.trigger', 'analyses.cancel', 'analyses.view_details',
            'issues.view', 'issues.dismiss',
            'reports.view', 'reports.generate', 'reports.download',
            'settings.view', 'settings.update',
            'subscriptions.view', 'subscriptions.manage',
        ],
        'eng_manager'  => [
            'organizations.view',
            'members.view', 'members.invite',
            'repositories.view', 'repositories.sync',
            'projects.view', 'projects.create', 'projects.update',
            'analyses.view', 'analyses.trigger', 'analyses.cancel', 'analyses.view_details',
            'issues.view', 'issues.dismiss',
            'reports.view', 'reports.generate', 'reports.download',
            'settings.view',
        ],
        'developer'    => [
            'organizations.view',
            'repositories.view',
            'projects.view',
            'analyses.view', 'analyses.trigger', 'analyses.view_details',
            'issues.view',
            'reports.view', 'reports.download',
        ],
        'qa_engineer'  => [
            'organizations.view',
            'repositories.view',
            'projects.view',
            'analyses.view', 'analyses.trigger', 'analyses.view_details',
            'issues.view', 'issues.dismiss',
            'reports.view', 'reports.generate', 'reports.download',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles with permissions
        foreach ($this->roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($perms === 'all') {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->syncPermissions($perms);
            }
        }
    }
}
