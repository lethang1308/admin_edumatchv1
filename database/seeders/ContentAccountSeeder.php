<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ContentAccountSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or Get 'content' role
        $role = Role::firstOrCreate(['name' => 'content']);

        // 2. Assign permissions
        // Ensure permissions exist before assigning
        Permission::firstOrCreate(['name' => 'access_admin']);
        Permission::firstOrCreate(['name' => 'manage_content']);

        $role->givePermissionTo(['access_admin', 'manage_content']);

        // 3. Create Content User
        $user = User::firstOrCreate([
            'email' => 'content@example.com',
        ], [
            'full_name' => 'Content Editor',
            'phone' => '0900000001', // Example phone
            'password' => Hash::make('password123'),
            'balance' => 0,
        ]);

        // 4. Assign Role
        $user->assignRole('content');

        $this->command->info('Content account created successfully.');
        $this->command->info('Email: content@example.com');
        $this->command->info('Password: password123');
    }
}
