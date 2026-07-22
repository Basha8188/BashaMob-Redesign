<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'view-dashboard',
        'view-products', 'create-products', 'edit-products', 'delete-products',
        'view-sales', 'create-sales', 'cancel-sales', 'refund-sales',
        'view-purchases', 'create-purchases', 'receive-purchases',
        'view-inventory', 'adjust-inventory',
        'view-customers', 'manage-customers',
        'view-suppliers', 'manage-suppliers',
        'view-repairs', 'create-repairs', 'update-repair-status', 'complete-repairs',
        'view-expenses', 'manage-expenses', 'view-reports',
        'view-staff', 'manage-staff', 'view-settings', 'manage-settings',
        'device_passports.view', 'device_passports.create', 'device_passports.update',
        'repair_inspections.view', 'repair_inspections.create', 'repair_inspections.update',
        'repair_quotations.create', 'repair_quotations.approve_override',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'Owner' => self::PERMISSIONS,
            'Manager' => array_values(array_diff(self::PERMISSIONS, ['manage-settings', 'manage-staff'])),
            'Cashier' => ['view-dashboard', 'view-sales', 'create-sales', 'refund-sales', 'view-customers', 'manage-customers', 'device_passports.view', 'device_passports.create'],
            'Technician' => ['view-dashboard', 'view-repairs', 'create-repairs', 'update-repair-status', 'complete-repairs', 'view-inventory', 'device_passports.view', 'repair_inspections.view', 'repair_inspections.create', 'repair_inspections.update', 'repair_quotations.create'],
            'Inventory Clerk' => ['view-dashboard', 'view-products', 'create-products', 'edit-products', 'view-inventory', 'adjust-inventory', 'view-purchases', 'create-purchases', 'receive-purchases', 'view-suppliers', 'manage-suppliers', 'device_passports.view'],
            'Accountant' => ['view-dashboard', 'view-sales', 'view-purchases', 'view-expenses', 'manage-expenses', 'view-reports'],
        ];

        foreach ($roles as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }
    }
}
