<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->paginate(10);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'photo'   => 'required|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $data = $request->only('name_en', 'name_ar','photo');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'تمت الإضافة بنجاح');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }



    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'photo'   => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $data = $request->only('name_en', 'name_ar');

        if ($request->hasFile('photo')) {
            // حذف الصورة القديمة إن وجدت
            if ($category->photo && Storage::disk('public')->exists($category->photo)) {
                Storage::disk('public')->delete($category->photo);
            }

            // رفع الصورة الجديدة
            $data['photo'] = $request->file('photo')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'تم التحديث بنجاح');
    }


    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'تم الحذف بنجاح');
    }
}
