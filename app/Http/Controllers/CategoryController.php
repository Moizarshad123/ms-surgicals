<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->latest()->get();
        $totalCategories = Category::count();
        $totalProducts = Product::count();

        return view('categories.index', compact('categories', 'totalCategories', 'totalProducts'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name']]);

        Category::create($request->only('name'));

        return back()->with('success', 'Category added successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id]]);

        $category->update($request->only('name'));

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete category that contains products. Please reassign or delete its products first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
