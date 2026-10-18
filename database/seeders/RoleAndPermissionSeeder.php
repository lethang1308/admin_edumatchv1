<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['access_admin', 'manage_users', 'manage_content'];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (['student', 'teacher', 'center', 'super_admin', 'admin', 'user'] as $name) {
            Role::findOrCreate($name, 'web');
        }
        Role::findByName('super_admin', 'web')->givePermissionTo($permissions);
        Role::findByName('admin', 'web')->givePermissionTo($permissions);

        // Add matching roles without removing existing content/custom permissions.
        // Seeding roles never creates an account with a publicly known password.
        User::query()->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $role = match ($user->role) {
                    'user' => 'student',
                    'admin' => 'super_admin',
                    default => $user->role,
                };
                if (in_array($role, ['student', 'teacher', 'center', 'super_admin'], true)) {
                    $user->assignRole($role);
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
