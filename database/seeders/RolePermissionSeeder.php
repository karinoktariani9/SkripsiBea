<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $viewDashboard = Permission::firstOrCreate(['name' => 'view dashboard']);
        $manageUsers = Permission::firstOrCreate(['name' => 'manage users']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions([$viewDashboard, $manageUsers]);
        $userRole->syncPermissions([$viewDashboard]);
    }
}
