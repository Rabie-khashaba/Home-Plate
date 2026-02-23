<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeliveryAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryAuthController extends Controller
{
    public function __construct(
        private readonly DeliveryAuthService $deliveryAuthService
    ) {
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
        ]);

        $response = $this->deliveryAuthService->startRegistration($data);

        return response()->json($response, 200);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:6',
        ]);

        $response = $this->deliveryAuthService->verifyRegistrationOtp(
            $data['phone'],
            $data['otp']
        );

        return response()->json($response, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'password' => 'required|string',
        ]);

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

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
        ]);

        $response = $this->deliveryAuthService->sendForgotPasswordOtp($data['phone']);

        return response()->json($response);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $response = $this->deliveryAuthService->resetPassword(
            $data['phone'],
            $data['otp'],
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
