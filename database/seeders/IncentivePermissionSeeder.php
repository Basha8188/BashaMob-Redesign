<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IncentivePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'incentives.view',
        'incentives.manage_plans',
        'incentives.manage_rules',
        'incentives.calculate',
        'incentives.review',
        'incentives.approve',
        'incentives.pay',
        'incentives.reopen_period',
        'incentives.add_adjustment',
        'incentives.view_all_employees',
        'incentives.view_own',
        'incentives.export',
        'incentives.manage_return_policy',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $rolePermissions = [
            'Owner' => self::PERMISSIONS,
            'Manager' => [
                'incentives.view',
                'incentives.manage_plans',
                'incentives.manage_rules',
                'incentives.calculate',
                'incentives.review',
                'incentives.view_all_employees',
                'incentives.view_own',
                'incentives.export',
                'incentives.manage_return_policy',
            ],
            'Accountant' => [
                'incentives.view',
                'incentives.review',
                'incentives.view_all_employees',
                'incentives.view_own',
                'incentives.export',
            ],
            'Cashier' => ['incentives.view_own'],
            'Technician' => ['incentives.view_own'],
            'Inventory Clerk' => ['incentives.view_own'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
