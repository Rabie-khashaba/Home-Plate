<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeliveryAuthRequest;
use App\Services\DeliveryAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryAuthController extends Controller
{
    public function __construct(
        private readonly DeliveryAuthService $deliveryAuthService
    ) {
    }

    public function register(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        try {
            $response = $this->deliveryAuthService->startRegistration($data);

            return response()->json($response, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Registration failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function verifyRegisterOtp(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        try {
            $response = $this->deliveryAuthService->verifyRegistrationOtp(
                $data['phone'],
                $data['otp']
            );

            return response()->json($response, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'OTP verification failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function login(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        try {
            $response = $this->deliveryAuthService->login($data['phone'], $data['password']);

            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Login failed.',
                'errors' => $e->errors(),
            ], 401);
        }
    }

    public function forgotPassword(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        $response = $this->deliveryAuthService->sendForgotPasswordOtp($data['phone']);

        return response()->json($response);
    }

    public function resendOtp(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        $response = $this->deliveryAuthService->resendOtp($data['phone']);

        return response()->json($response);
    }

    public function verifyForgotPasswordOtp(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        try {
            $response = $this->deliveryAuthService->verifyForgotPasswordOtp(
                $data['phone'],
                $data['otp']
            );

            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'OTP verification failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function resetPassword(DeliveryAuthRequest $request)
    {
        $data = $request->validated();

        $response = $this->deliveryAuthService->resetPassword(
            $data['phone'],
            $data['password']
        );

        return response()->json($response);
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
