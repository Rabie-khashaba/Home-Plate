<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogger;

class AppUserController extends Controller
{
    public function index(Request $request)
    {
        $query = AppUser::with(['city', 'area']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Date filter
        $dateFilter = $request->get('date_filter');
        $from       = $request->get('from');
        $to         = $request->get('to');

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        // Status filter
        if ($request->get('status') === 'active')   $query->where('is_active', true);
        if ($request->get('status') === 'inactive') $query->where('is_active', false);

        $users = $query->latest()->paginate(15)->withQueryString();

        // Stats — apply same date filter
        $statsQuery = fn() => AppUser::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                                     ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                                     ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                                     ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                                     ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                     ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'    => $statsQuery()->count(),
            'active'   => $statsQuery()->where('is_active', true)->count(),
            'inactive' => $statsQuery()->where('is_active', false)->count(),
            'today'    => AppUser::whereDate('created_at', today())->count(),
        ];

        return view('app_users.index', compact('users', 'stats'));
    }

    public function create()
    {
        $cities = City::all();
        $areas = Area::all();
        return view('app_users.create', compact('cities', 'areas'));
    }

    public function store(Request $request)
    {
        //return $request;
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

        $user = AppUser::create($data);
        ActivityLogger::log('created', 'Created user: ' . $user->name, $user);

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
        ActivityLogger::log('updated', 'Updated user: ' . $app_user->name, $app_user);

        return redirect()->route('app_users.index')->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function destroy(AppUser $app_user)
    {
        if ($app_user->photo) {
            Storage::disk('public')->delete($app_user->photo);
        }

        ActivityLogger::log('deleted', 'Deleted user: ' . $app_user->name, $app_user);
        $app_user->delete();

        return redirect()->route('app_users.index')->with('success', 'تم حذف المستخدم بنجاح.');
    }

    public function toggleActive(AppUser $app_user)
    {
        $app_user->is_active = !$app_user->is_active;
        $app_user->save();
        ActivityLogger::log('updated', ($app_user->is_active ? 'Activated' : 'Deactivated') . ' user: ' . $app_user->name, $app_user);

        return redirect()->route('app_users.index')->with('success', 'تم تحديث حالة المستخدم.');
    }
}
