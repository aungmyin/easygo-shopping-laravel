<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index()
    {
        $products = Product::withTrashed()
            ->with('category')
            ->when(request('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'category_id'          => 'required|exists:categories,id',
            'price'                => 'required|numeric|min:0',
            'sale_price'           => 'nullable|numeric',
            'stock'                => 'required|integer',
            'description'          => 'nullable|string',
            'short_description'    => 'nullable|string',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_delivery_friendly' => 'boolean',
            'thumbnail'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $product = Product::create($data);

        return new ProductResource($product->load('category'));
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('category', 'images'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'                 => 'sometimes',
            'category_id'          => 'sometimes|exists:categories,id',
            'price'                => 'sometimes|numeric',
            'sale_price'           => 'nullable|numeric',
            'stock'                => 'sometimes|integer',
            'description'          => 'nullable|string',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_delivery_friendly' => 'boolean',
            'thumbnail'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
        }

        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $product->update($data);

        return new ProductResource($product->fresh('category'));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
