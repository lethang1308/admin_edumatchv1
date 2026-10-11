<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "Starting Permission Verification...\n";

try {
    echo "1. Creating Permission 'test-permission'...\n";
    $permission = Permission::firstOrCreate(['name' => 'test-permission', 'guard_name' => 'web']);
    echo "   Permission created: " . $permission->name . "\n";

    echo "2. Creating Role 'test-role'...\n";
    $role = Role::firstOrCreate(['name' => 'test-role', 'guard_name' => 'web']);
    echo "   Role created: " . $role->name . "\n";

    echo "3. Assigning Permission to Role...\n";
    $role->givePermissionTo($permission);
    echo "   Permission assigned.\n";

    echo "4. Creating User 'testuser'...\n";
    $user = User::updateOrCreate(
        ['email' => 'testuser@example.com'],
        [
            'name' => 'testuser',
            'full_name' => 'Test User',
            'password' => Hash::make('password'),
            'role' => 'user',
            'phone' => '0123456789'
        ]
    );
    echo "   User created: " . $user->email . "\n";

    echo "5. Assigning Role to User...\n";
    $user->assignRole($role);
    echo "   Role assigned.\n";

    echo "6. Verifying User Permissions...\n";
    if ($user->can('test-permission')) {
        echo "   [PASS] User has 'test-permission'.\n";
    } else {
        echo "   [FAIL] User does NOT have 'test-permission'.\n";
        exit(1);
    }

    if ($user->hasRole('test-role')) {
        echo "   [PASS] User has 'test-role'.\n";
    } else {
        echo "   [FAIL] User does NOT have 'test-role'.\n";
        exit(1);
    }

    // Cleanup
    echo "Cleaning up...\n";
    $user->delete();
    // Keep role/permission for debugging or manually delete:
    // $role->delete(); 
    // $permission->delete();

    echo "Verification Completed Successfully!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
