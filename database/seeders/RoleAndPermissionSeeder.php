<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::create(['name' => 'access_admin']);
        Permission::create(['name' => 'manage_users']);
        Permission::create(['name' => 'manage_content']);

        // create roles and assign created permissions

        // this can be done as separate statements
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('access_admin');
        $role->givePermissionTo('manage_users');
        $role->givePermissionTo('manage_content');

        $role = Role::create(['name' => 'user']);

        // Create default admin if not exists
        $admin = User::firstOrCreate([
            'phone' => '0909090909',
        ], [
            'full_name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $admin->assignRole('admin');

        // Sync existing users
        $users = User::where('id', '!=', $admin->id)->get();
        foreach ($users as $user) {
            if ($user->role === 'admin') {
                $user->assignRole('admin');
            } else {
                $user->assignRole('user');
            }
        }
    }
}
