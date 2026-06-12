<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Roles map to the canonical `users.role` column values.
     */
    private const GUARD = 'web';

    /**
     * Permission matrix: role => abilities it should hold.
     *
     * @var array<string, list<string>>
     */
    private const MATRIX = [
        'admin' => [
            'manage-users',
            'manage-subdivisions',
            'manage-houses',
            'manage-residents',
            'manage-incidents',
            'view-incidents',
            'manage-visitors',
            'view-analytics',
        ],
        'staff' => [
            'manage-incidents',
            'view-incidents',
            'manage-residents',
            'view-analytics',
        ],
        'security' => [
            'manage-visitors',
            'view-incidents',
        ],
        'resident' => [
            'report-incidents',
            'view-incidents',
            'approve-visitor-requests',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = collect(self::MATRIX)->flatten()->unique()->values();

        foreach ($allPermissions as $permissionName) {
            Permission::findOrCreate($permissionName, self::GUARD);
        }

        foreach (self::MATRIX as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, self::GUARD);
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}