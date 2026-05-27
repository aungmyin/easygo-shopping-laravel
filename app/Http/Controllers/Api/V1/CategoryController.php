<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\{CategoryResource, ProductResource};

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and gadgets'],
                ['id' => 2, 'name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Fashion and apparel'],
                ['id' => 3, 'name' => 'Books', 'slug' => 'books', 'description' => 'Books and publications'],
            ]
        ]);
    }

    public function show(string $slug)
    {
        $categories = [
            'electronics' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and gadgets'],
            'clothing' => ['id' => 2, 'name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Fashion and apparel'],
            'books' => ['id' => 3, 'name' => 'Books', 'slug' => 'books', 'description' => 'Books and publications'],
        ];

        return response()->json(['data' => $categories[$slug] ?? null], isset($categories[$slug]) ? 200 : 404);
    }

    public function products(string $slug)
    {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'Sample Product', 'slug' => 'sample-product', 'price' => 99.99],
            ]
        ]);
    }
}
