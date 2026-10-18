<?php

use App\Http\Controllers\Api\AdministrativeAreaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Middleware\EnsureActiveAccount;
use Illuminate\Support\Facades\Route;

// Keep both /api/auth/* and legacy /api/* clients on the same implementation.
$authRoutes = function () {
    Route::post('/register/send-otp', [AuthController::class, 'sendRegistrationOtp'])->middleware('throttle:registration-otp-send');
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:password-reset-send');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:password-reset-verify');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/change-password', [PasswordController::class, 'change'])->middleware(EnsureActiveAccount::class);
    });
};

Route::prefix('auth')->group($authRoutes);
Route::group([], $authRoutes);

Route::get('/administrative-areas', [AdministrativeAreaController::class, 'index']);
Route::middleware(['auth:sanctum', EnsureActiveAccount::class])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/student/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'avatar']);
});
