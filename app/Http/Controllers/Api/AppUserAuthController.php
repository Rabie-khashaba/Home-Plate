<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AppUser;

class AppUserAuthController extends Controller
{

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:app_users,email',
            'phone' => 'required|unique:app_users,phone',
            'password' => 'required|string|min:6',
            'gender' => 'nullable|in:male,female',
            'photo' => 'nullable|image',
            'dob' => 'nullable|date',
            'city' => 'required|string',
            'area' => 'required|string',
            'delivery_addresses' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if($request->hasFile('photo')){
            $data['photo'] = $request->file('photo')->store('app_users', 'public');
        }

        $data['password'] = Hash::make($data['password']);

        $user = AppUser::create($data);

        // إنشاء توكن تلقائي بعد التسجيل
        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل المستخدم بنجاح',
            'user' => $user,
            'token' => $token
        ], 201);
    }


    public function login(Request $request)
    {
        $request->validate([
        'phone' => 'required|string',
        'password' => 'required|string',
        ]);

        $user = AppUser::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة'
            ], 401);
        }

        if(!$user->is_active) {
            return response()->json([
                'message' => 'الحساب غير مفعل بعد'
            ], 403);
        }

        // إنشاء توكن باستخدام Laravel Sanctum
        $token = $user->createToken('app_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => $user,
            'token' => $token
        ]);
    }


    public function logout(Request $request)
    {
        // حذف التوكن الحالي فقط
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}

