<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AppUserController extends Controller
{
    public function index()
    {
        $users = AppUser::with(['city', 'area'])->paginate(10);
        return view('app_users.index', compact('users'));
    }

    public function create()
    {
        $cities = City::all();
        $areas = Area::all();
        return view('app_users.create', compact('cities', 'areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:app_users,email',
            'phone' => 'required|unique:app_users,phone',
            'password' => 'required|string|min:6',
            'gender' => 'nullable|in:male,female',
            'photo' => 'nullable|image',
            'dob' => 'nullable|date',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_addresses' => 'nullable|string',
            'location' => 'required|url|max:255',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('app_users', 'public');
        }

        $data['password'] = Hash::make($data['password']);

        AppUser::create($data);

        return redirect()->route('app_users.index')->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function show(AppUser $app_user)
    {
        $app_user->load(['city', 'area']);
        return view('app_users.show', compact('app_user'));
    }

    public function edit(AppUser $app_user)
    {
        $cities = City::all();
        $areas = Area::all();
        return view('app_users.edit', compact('app_user', 'cities', 'areas'));
    }

    public function update(Request $request, AppUser $app_user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:app_users,email,' . $app_user->id,
            'phone' => 'required|unique:app_users,phone,' . $app_user->id,
            'password' => 'nullable|string|min:6',
            'gender' => 'nullable|in:male,female',
            'photo' => 'nullable|image',
            'dob' => 'nullable|date',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_addresses' => 'nullable|string',
            'location' => 'required|url|max:500',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            if ($app_user->photo) {
                Storage::disk('public')->delete($app_user->photo);
            }
            $data['photo'] = $request->file('photo')->store('app_users', 'public');
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $app_user->update($data);

        return redirect()->route('app_users.index')->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function destroy(AppUser $app_user)
    {
        if ($app_user->photo) {
            Storage::disk('public')->delete($app_user->photo);
        }

        $app_user->delete();

        return redirect()->route('app_users.index')->with('success', 'تم حذف المستخدم بنجاح.');
    }

    public function toggleActive(AppUser $app_user)
    {
        $app_user->is_active = !$app_user->is_active;
        $app_user->save();

        return redirect()->route('app_users.index')->with('success', 'تم تحديث حالة المستخدم.');
    }
}
