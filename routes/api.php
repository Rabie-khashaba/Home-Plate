<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppUserAuthController;
use App\Http\Controllers\Api\DeliveryAuthController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::post('login', [AppUserAuthController::class, 'login']);
Route::post('/register', [AppUserAuthController::class, 'register']);


Route::middleware('auth:sanctum')->group(function() {

    Route::post('/logout', [AppUserAuthController::class, 'logout']);

});

Route::prefix('delivery/auth')->group(function () {
    Route::post('/register', [DeliveryAuthController::class, 'register']);
    Route::post('/register/verify-otp', [DeliveryAuthController::class, 'verifyRegisterOtp']);
    Route::post('/login', [DeliveryAuthController::class, 'login']);
    Route::post('/forgot-password', [DeliveryAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [DeliveryAuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->post('/logout', [DeliveryAuthController::class, 'logout']);
});



