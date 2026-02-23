<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUserAuthController;
use App\Http\Controllers\Api\DeliveryAuthController;
use App\Http\Controllers\Api\GeneralRequestController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\VendorAuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::prefix('delivery/auth')->group(function () {
    Route::post('/register', [DeliveryAuthController::class, 'register']);
    Route::post('/register/verify-otp', [DeliveryAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    Route::post('/forgot-password', [DeliveryAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [DeliveryAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [DeliveryAuthController::class, 'logout']);
});

Route::prefix('app-user/auth')->group(function () {
    Route::post('/register', [AppUserAuthController::class, 'register']);
    Route::post('/register/verify-otp', [AppUserAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [AppUserAuthController::class, 'login']);
    Route::post('/forgot-password', [AppUserAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AppUserAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [AppUserAuthController::class, 'logout']);
});

Route::prefix('vendor/auth')->group(function () {
    Route::post('/register', [VendorAuthController::class, 'register']);
    Route::post('/register/verify-otp', [VendorAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [VendorAuthController::class, 'login']);
    Route::post('/forgot-password', [VendorAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [VendorAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [VendorAuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/app-user/{id}', [ApiProfileController::class, 'appUser']);
    Route::get('/vendor/{id}', [ApiProfileController::class, 'vendor']);
    Route::get('/delivery/{id}', [ApiProfileController::class, 'delivery']);
});

Route::prefix('general')->group(function () {
    Route::get('/categories', [GeneralRequestController::class, 'categories']);
    Route::get('/subcategories', [GeneralRequestController::class, 'subcategories']);
    Route::get('/countries', [GeneralRequestController::class, 'countries']);
    Route::get('/cities', [GeneralRequestController::class, 'cities']);
    Route::get('/areas', [GeneralRequestController::class, 'areas']);
});
