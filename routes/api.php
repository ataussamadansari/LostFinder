<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\RideController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\PassengerController;

// Public Auth Endpoints
Route::prefix('v1/auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/admin-login', [AuthController::class, 'adminLogin']);
});

// Public Driver/Ride Info
Route::get('/v1/ride/driver-info/{qr_token}', [RideController::class, 'getDriverByQr']);

// Server-Driven App Bootstrap, Configurations & Ads
Route::prefix('v1/app')->group(function () {
    Route::get('/bootstrap', [\App\Http\Controllers\Api\V1\AppConfigController::class, 'bootstrap']);
    Route::get('/config', [\App\Http\Controllers\Api\V1\AppConfigController::class, 'bootstrap']);
    Route::get('/promotions', [\App\Http\Controllers\Api\V1\AppConfigController::class, 'promotions']);
    Route::post('/promotions/{id}/click', [\App\Http\Controllers\Api\V1\AppConfigController::class, 'recordBannerClick']);
    Route::get('/categories', [\App\Http\Controllers\Api\V1\AppConfigController::class, 'categories']);
});

// Protected Auth Endpoints (Any authenticated user)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::match(['put', 'patch', 'post'], '/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Driver Protected Routes
Route::middleware(['auth:sanctum', 'role:driver'])->prefix('v1/driver')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => true, 'message' => 'pong']));

    // Profile & Registration
    Route::post('/profile', [DriverController::class, 'register']);
    Route::get('/profile', [DriverController::class, 'profile']);
    Route::match(['put', 'patch', 'post'], '/profile/update', [DriverController::class, 'updateProfile']);

    // QR Management
    Route::get('/qr', [DriverController::class, 'getQr']);
    Route::post('/qr/regenerate', [DriverController::class, 'regenerateQr']);

    // Rides & Logs
    Route::get('/rides', [DriverController::class, 'rideHistory']);
    Route::get('/rides/{id}', [DriverController::class, 'showRide']);

    // Claims & Handover Verification
    Route::get('/claims', [DriverController::class, 'listClaims']);
    Route::get('/claims/{id}', [DriverController::class, 'showClaim']);
    Route::patch('/claims/{id}/status', [DriverController::class, 'updateClaimStatus']);
    Route::post('/claims/{id}/verify-handover', [DriverController::class, 'verifyHandover']);

    // Device Token & Dashboard Stats
    Route::patch('/fcm', [DriverController::class, 'updateFcm']);
    Route::get('/stats', [DriverController::class, 'stats']);
});

// Tourist Protected Routes
Route::middleware(['auth:sanctum', 'role:tourist'])->prefix('v1/tourist')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => true, 'message' => 'pong']));

    // Profile & Preferences
    Route::get('/profile', [PassengerController::class, 'profile']);
    Route::patch('/profile', [PassengerController::class, 'updateProfile']);

    // Rides
    Route::post('/ride/scan', [RideController::class, 'scanRide']);
    Route::get('/rides', [RideController::class, 'touristRides']);
    Route::get('/rides/{id}', [RideController::class, 'showRide']);
    Route::post('/rides/{id}/complete', [RideController::class, 'completeRide']);

    // Lost Claims
    Route::get('/claims', [ClaimController::class, 'index']);
    Route::post('/claims', [ClaimController::class, 'store']);
    Route::get('/claims/{id}', [ClaimController::class, 'show']);
    Route::delete('/claims/{id}', [ClaimController::class, 'cancel']);
});
