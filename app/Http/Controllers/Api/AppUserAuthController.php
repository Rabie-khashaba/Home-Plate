<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppUserAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppUserAuthController extends Controller
{
    public function __construct(
        private readonly AppUserAuthService $appUserAuthService
    ) {
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_addresses' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        return response()->json($this->appUserAuthService->startRegistration($data));
    }

    public function verifyRegisterOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:6',
        ]);

        return response()->json(
            $this->appUserAuthService->verifyRegistrationOtp($data['phone'], $data['otp']),
            201
        );
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'password' => 'required|string',
        ]);

        try {
            return response()->json(
                $this->appUserAuthService->login($data['phone'], $data['password'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Login failed.',
                'errors' => $e->errors(),
            ], 401);
        }
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        return response()->json(
            $this->appUserAuthService->sendForgotPasswordOtp($data['phone'])
        );
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        return response()->json(
            $this->appUserAuthService->resetPassword($data['phone'], $data['otp'], $data['password'])
        );
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}