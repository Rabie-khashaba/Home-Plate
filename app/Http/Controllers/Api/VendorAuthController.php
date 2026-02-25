<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VendorAuthRequest;
use App\Services\VendorAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorAuthController extends Controller
{
    public function __construct(
        private readonly VendorAuthService $vendorAuthService
    ) {
    }

    public function register(VendorAuthRequest $request)
    {
        return response()->json($this->vendorAuthService->startRegistration($request->validated()));
    }

    public function verifyRegisterOtp(VendorAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->vendorAuthService->verifyRegistrationOtp($data['phone'], $data['otp']),
            201
        );
    }

    public function login(VendorAuthRequest $request)
    {
        $data = $request->validated();

        try {
            return response()->json(
                $this->vendorAuthService->login($data['phone'], $data['password'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Login failed.',
                'errors' => $e->errors(),
            ], 401);
        }
    }

    public function forgotPassword(VendorAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->vendorAuthService->sendForgotPasswordOtp($data['phone'])
        );
    }

    public function resendOtp(VendorAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->vendorAuthService->resendOtp($data['phone'])
        );
    }

    public function verifyForgotPasswordOtp(VendorAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->vendorAuthService->verifyForgotPasswordOtp($data['phone'], $data['otp'])
        );
    }

    public function resetPassword(VendorAuthRequest $request)
    {
        $data = $request->validated();

        return response()->json(
            $this->vendorAuthService->resetPassword($data['phone'], $data['password'])
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
