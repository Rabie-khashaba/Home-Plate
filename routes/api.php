<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUserAuthController;
use App\Http\Controllers\Api\DeliveryAuthController;
use App\Http\Controllers\Api\VendorAuthController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\AppUserStatusController;
use App\Http\Controllers\Api\DeliveryStatusController;
use App\Http\Controllers\Api\VendorStatusController;
use App\Http\Controllers\Api\GeneralRequestController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
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


Route::prefix('general')->group(function () {
    Route::get('/categories', [GeneralRequestController::class, 'categories']);
    Route::get('/subcategories', [GeneralRequestController::class, 'subcategories']);
    Route::get('/countries', [GeneralRequestController::class, 'countries']);
    Route::get('/cities', [GeneralRequestController::class, 'cities']);
    Route::get('/areas', [GeneralRequestController::class, 'areas']);
});



Route::prefix('delivery/auth')->group(function () {
    Route::post('/register', [DeliveryAuthController::class, 'register']);
    Route::post('/register/verify-otp', [DeliveryAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    Route::post('/forgot-password', [DeliveryAuthController::class, 'forgotPassword']);
    Route::post('/resend-otp', [DeliveryAuthController::class, 'resendOtp']);
    Route::post('/verify-forgot-password-otp', [DeliveryAuthController::class, 'verifyForgotPasswordOtp']);
    Route::post('/reset-password', [DeliveryAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [DeliveryAuthController::class, 'logout']);
});

Route::prefix('app-user/auth')->group(function () {
    Route::post('/register', [AppUserAuthController::class, 'register']);
    Route::post('/register/verify-otp', [AppUserAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [AppUserAuthController::class, 'login']);
    Route::post('/forgot-password', [AppUserAuthController::class, 'forgotPassword']);
    Route::post('/resend-otp', [AppUserAuthController::class, 'resendOtp']);
    Route::post('/verify-forgot-password-otp', [AppUserAuthController::class, 'verifyForgotPasswordOtp']);
    Route::post('/reset-password', [AppUserAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [AppUserAuthController::class, 'logout']);
});

Route::prefix('vendor/auth')->group(function () {
    Route::post('/register', [VendorAuthController::class, 'register']);
    Route::post('/register/verify-otp', [VendorAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [VendorAuthController::class, 'login']);
    Route::post('/forgot-password', [VendorAuthController::class, 'forgotPassword']);
    Route::post('/resend-otp', [VendorAuthController::class, 'resendOtp']);
    Route::post('/verify-forgot-password-otp', [VendorAuthController::class, 'verifyForgotPasswordOtp']);
    Route::post('/reset-password', [VendorAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [VendorAuthController::class, 'logout']);
});



Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/app-user/{id}', [ApiProfileController::class, 'appUser']);
    Route::get('/vendor/{id}', [ApiProfileController::class, 'vendor']);
    Route::get('/delivery/{id}', [ApiProfileController::class, 'delivery']);
    Route::post('/app-user/{id}', [ApiProfileController::class, 'updateAppUser']);
    Route::post('/vendor/{id}', [ApiProfileController::class, 'updateVendor']);
    Route::post('/delivery/{id}', [ApiProfileController::class, 'updateDelivery']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('items')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::post('/{id}/approve', [ItemController::class, 'approve']);
        Route::post('/{id}/reject', [ItemController::class, 'reject']);
    });

    Route::prefix('vendor/items')->group(function () {
        Route::get('/', [ItemController::class, 'vendorIndex']);
        Route::post('/', [ItemController::class, 'store']);
        Route::post('/{id}', [ItemController::class, 'update']);
        Route::post('/{id}/publish', [ItemController::class, 'publish']);
        Route::post('/{id}/pause', [ItemController::class, 'pause']);
    });

    Route::prefix('vendor')->group(function () {
        Route::post('/{id}/status/pending', [VendorStatusController::class, 'setPending']);
        Route::post('/{id}/status/approved', [VendorStatusController::class, 'approve']);
        Route::post('/{id}/status/rejected', [VendorStatusController::class, 'reject']);
        Route::post('/{id}/activate', [VendorStatusController::class, 'activate']);
        Route::post('/{id}/deactivate', [VendorStatusController::class, 'deactivate']);
    });

    Route::prefix('delivery')->group(function () {
        Route::post('/{id}/status/pending', [DeliveryStatusController::class, 'setPending']);
        Route::post('/{id}/status/approved', [DeliveryStatusController::class, 'approve']);
        Route::post('/{id}/status/rejected', [DeliveryStatusController::class, 'reject']);
        Route::post('/{id}/activate', [DeliveryStatusController::class, 'activate']);
        Route::post('/{id}/deactivate', [DeliveryStatusController::class, 'deactivate']);
    });

    Route::prefix('app-user')->group(function () {
        Route::post('/{id}/activate', [AppUserStatusController::class, 'activate']);
        Route::post('/{id}/deactivate', [AppUserStatusController::class, 'deactivate']);
    });
});
