<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Default Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Admin User',
            'password' => 'password', // By default cast to hashed
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Default Regular User
        $user = User::firstOrCreate([
            'email' => 'user@user.com',
        ], [
            'name' => 'Regular User',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('user');
    }
}
