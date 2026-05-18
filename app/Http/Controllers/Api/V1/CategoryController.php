<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\{CategoryResource, ProductResource};

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->with('children')
            ->firstOrFail();

        return new CategoryResource($category);
    }

    public function products(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with('category', 'images')
            ->paginate(request('per_page', 12));

        return ProductResource::collection($products);
    }
}
