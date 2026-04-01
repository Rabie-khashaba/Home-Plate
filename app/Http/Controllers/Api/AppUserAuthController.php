<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserAuthRequest;
use App\Services\AppUserAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppUserAuthController extends Controller
{
    public function __construct(
        private readonly AppUserAuthService $appUserAuthService
    ) {
    }

    public function register(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        try {
            return response()->json($this->appUserAuthService->startRegistration($data));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function verifyRegisterOtp(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        try {
            return response()->json(
                $this->appUserAuthService->verifyRegistrationOtp($data['phone'], $data['otp']),
                201
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'OTP verification failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function login(AppUserAuthRequest $request)
    {
        $data = $request->validated();

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

    public function forgotPassword(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->appUserAuthService->sendForgotPasswordOtp($data['phone'])
        );
    }

    public function resendOtp(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->appUserAuthService->resendOtp($data['phone'])
        );
    }

    public function verifyForgotPasswordOtp(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        try {
            return response()->json(
                $this->appUserAuthService->verifyForgotPasswordOtp($data['phone'], $data['otp'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'OTP verification failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function resetPassword(AppUserAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->appUserAuthService->resetPassword($data['phone'], $data['password'])
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
