<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'bots.create',
            'bots.edit',
            'bots.delete',
            'bots.view',
            'calls.view',
            'calls.delete',
            'analytics.view',
            'billing.manage',
            'team.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions($permissions);

        $tenantAdmin = Role::firstOrCreate(['name' => 'tenant_admin']);
        $tenantAdmin->syncPermissions([
            'bots.create', 'bots.edit', 'bots.delete', 'bots.view',
            'calls.view', 'calls.delete',
            'analytics.view',
            'billing.manage',
            'team.manage',
            'settings.manage',
        ]);

        $tenantManager = Role::firstOrCreate(['name' => 'tenant_manager']);
        $tenantManager->syncPermissions([
            'bots.create', 'bots.edit', 'bots.view',
            'calls.view',
            'analytics.view',
            'team.manage',
        ]);

        $tenantViewer = Role::firstOrCreate(['name' => 'tenant_viewer']);
        $tenantViewer->syncPermissions([
            'bots.view',
            'calls.view',
            'analytics.view',
        ]);
    }
}
