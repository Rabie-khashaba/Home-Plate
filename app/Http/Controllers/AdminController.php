<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $admins = User::whereIn('type', ['admin', 'user'])->paginate(10);
        return view('admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admins.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'required|in:admin,user',
        ]);

        User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'type' => $request->type,
        ]);

        return redirect()->route('admins.index')->with('success', 'Admin created successfully');
    }

    public function show(User $admin)
    {
        return view('admins.show', compact('admin'));
    }

    public function edit(User $admin)
    {
        return view('admins.edit', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone,' . $admin->id,
            'password' => 'nullable|string|min:6|confirmed',
            'type' => 'required|in:admin,user',
        ]);

        $admin->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'type' => $request->type,
            'password' => $request->password ? Hash::make($request->password) : $admin->password,
        ]);

        return redirect()->route('admins.index')->with('success', 'Admin updated successfully');
    }

    public function destroy(User $admin)
    {
        $admin->delete();
        return redirect()->route('admins.index')->with('success', 'Admin deleted successfully');
    }
}
