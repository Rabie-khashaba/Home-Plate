<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;

class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = Subcategory::with('category')->paginate(10);
        return view('subcategories.index', compact('subcategories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        Subcategory::create($request->all());
        return redirect()->route('subcategories.index')->with('success', 'Subcategory created successfully');
    }

    public function show(Subcategory $subcategory)
    {
        return view('subcategories.show', compact('subcategory'));
    }

    public function edit(Subcategory $subcategory)
    {
        $categories = Category::all();
        return view('subcategories.edit', compact('subcategory', 'categories'));
    }

    public function update(Request $request, Subcategory $subcategory)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ]);

        $subcategory->update($request->all());
        return redirect()->route('subcategories.index')->with('success', 'Subcategory updated successfully');
    }

    public function destroy(Subcategory $subcategory)
    {
        $subcategory->delete();
        return redirect()->route('subcategories.index')->with('success', 'Subcategory deleted successfully');
    }
}
