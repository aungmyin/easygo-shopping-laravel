<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')
            ->when(request('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string',
            'description' => 'nullable|string',
            'parent_id'  => 'nullable|exists:categories,id',
            'is_active'  => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $category = Category::create($data);

        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category->load('children'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'       => 'sometimes|string',
            'description' => 'nullable|string',
            'parent_id'  => 'nullable|exists:categories,id',
            'is_active'  => 'boolean',
        ]);

        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return new CategoryResource($category->fresh('children'));
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
