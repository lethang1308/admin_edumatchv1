<?php

use Illuminate\Support\Facades\Http;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';

// Fix: Bootstrap Laravel properly
$app = require_once __DIR__ . '/bootstrap/app.php';

// Create Console Kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$baseUrl = 'http://localhost:8000/api';

echo "--- Starting Verification ---\n";

// 1. Test Login
echo "\n1. Testing Login...\n";
$response = Http::post("$baseUrl/login", [
    'phone' => '0909090909',
    'password' => 'password123',
]);

if ($response->successful()) {
    $data = $response->json();
    $token = $data['access_token'];
    echo "Login Successful. Token: " . substr($token, 0, 10) . "...\n";
} else {
    echo "Login Failed: " . $response->body() . "\n";
    exit(1);
}

// 2. Test Get User Profile
echo "\n2. Testing Get User Profile...\n";
$response = Http::withToken($token)->get("$baseUrl/user");

if ($response->successful()) {
    $data = $response->json();
    $user = $data['user'];
    $roles = $data['roles'];
    $permissions = $data['permissions'];

    echo "User Profile Retrieved: " . $user['full_name'] . "\n";
    echo "Roles: " . implode(', ', $roles) . "\n";
    echo "Permissions: " . implode(', ', $permissions) . "\n";
} else {
    echo "Get User Profile Failed: " . $response->body() . "\n";
}

// 3. Test Refresh Token
echo "\n3. Testing Refresh Token...\n";
$response = Http::withToken($token)->post("$baseUrl/refresh");

if ($response->successful()) {
    $data = $response->json();
    $newToken = $data['access_token'];
    echo "Refresh Token Successful. New Token: " . substr($newToken, 0, 10) . "...\n";
    $token = $newToken; // Update token for subsequent requests
} else {
    echo "Refresh Token Failed: " . $response->body() . "\n";
}

// 4. Test Verification of Admin Permissions (Internal Check)
echo "\n4. Verifying Admin Permissions via Model...\n";
$admin = User::where('phone', '0909090909')->first();
if ($admin->can('access_admin')) {
    echo "Admin has 'access_admin' permission: PASS\n";
} else {
    echo "Admin has 'access_admin' permission: FAIL\n";
}

// 5. Test Logout
echo "\n5. Testing Logout...\n";
$response = Http::withToken($token)->post("$baseUrl/logout");

if ($response->successful()) {
    echo "Logout Successful.\n";
} else {
    echo "Logout Failed: " . $response->body() . "\n";
}

echo "\n--- Verification Complete ---\n";
