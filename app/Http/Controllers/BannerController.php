<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderBy('id')->paginate(20);
        $stats = [
            'total'    => Banner::count(),
            'active'   => Banner::where('is_active', true)->count(),
            'inactive' => Banner::where('is_active', false)->count(),
        ];
        return view('banners.index', compact('banners', 'stats'));
    }

    public function create()
    {
        return view('banners.form', ['banner' => new Banner()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'nullable|string|max:255',
            'image'      => 'required|image|max:5120',
            'link'       => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $request->input('sort_order', 0);
        $data['image']      = $request->file('image')->store('banners', 'public');

        $banner = Banner::create($data);
        ActivityLogger::log('created', 'Added banner: ' . ($banner->title ?? 'Banner #'.$banner->id), $banner);

        return redirect()->route('banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner)
    {
        return view('banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title'      => 'nullable|string|max:255',
            'image'      => 'nullable|image|max:5120',
            'link'       => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $request->input('sort_order', 0);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        } else {
            unset($data['image']);
        }

        $banner->update($data);
        ActivityLogger::log('updated', 'Updated banner: ' . ($banner->title ?? 'Banner #'.$banner->id), $banner);

        return redirect()->route('banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image);
        ActivityLogger::log('deleted', 'Deleted banner: ' . ($banner->title ?? 'Banner #'.$banner->id), $banner);
        $banner->delete();
        return back()->with('success', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('success', 'Banner status updated.');
    }
}
