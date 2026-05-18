<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with('category', 'images');

        if ($request->category) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }
        if ($request->min_price) $query->where('price', '>=', $request->min_price);
        if ($request->max_price) $query->where('price', '<=', $request->max_price);
        if ($request->on_sale)   $query->onSale();
        if ($request->featured)  $query->featured();

        match ($request->sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest'     => $query->latest(),
            default      => $query->orderBy('sort_order'),
        };

        return ProductResource::collection(
            $query->paginate($request->input('per_page', 12))
        );
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with('category', 'images')
            ->firstOrFail();

        return new ProductResource($product);
    }

    public function featured()
    {
        $products = Product::active()
            ->featured()
            ->with('category', 'images')
            ->take(8)
            ->get();

        return ProductResource::collection($products);
    }

    public function onSale()
    {
        $products = Product::active()
            ->onSale()
            ->with('category', 'images')
            ->paginate(request('per_page', 12));

        return ProductResource::collection($products);
    }

    public function deliveryFriendly()
    {
        $products = Product::active()
            ->deliveryFriendly()
            ->with('category', 'images')
            ->paginate(request('per_page', 12));

        return ProductResource::collection($products);
    }

    public function search(Request $request)
    {
        $q = $request->validate(['q' => 'required|string|min:2'])['q'];

        $products = Product::active()
            ->with('category', 'images')
            ->where(fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%")
            )
            ->paginate(12);

        return ProductResource::collection($products);
    }
}
