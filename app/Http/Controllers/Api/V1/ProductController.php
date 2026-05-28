<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function getMockProducts()
    {
        return [
            ['id' => 1, 'name' => 'Premium Headphones', 'slug' => 'premium-headphones', 'price' => 199.99, 'description' => 'High-quality wireless headphones with noise cancellation and 30-hour battery life.', 'stock' => 15, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
            ['id' => 2, 'name' => 'Wireless Mouse', 'slug' => 'wireless-mouse', 'price' => 49.99, 'description' => 'Ergonomic wireless mouse with precision tracking and 2-year battery life.', 'stock' => 32, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
            ['id' => 3, 'name' => 'USB-C Cable', 'slug' => 'usb-c-cable', 'price' => 15.99, 'description' => 'Durable USB-C cable with 100W fast charging capability.', 'stock' => 50, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
            ['id' => 4, 'name' => 'Laptop Stand', 'slug' => 'laptop-stand', 'price' => 79.99, 'description' => 'Adjustable aluminum laptop stand for better ergonomics.', 'stock' => 20, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
            ['id' => 5, 'name' => 'Monitor Light', 'slug' => 'monitor-light', 'price' => 59.99, 'description' => 'Auto-dimming monitor light bar that reduces eye strain.', 'stock' => 18, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
            ['id' => 6, 'name' => 'Mechanical Keyboard', 'slug' => 'mechanical-keyboard', 'price' => 129.99, 'description' => 'RGB mechanical keyboard with customizable switches.', 'stock' => 25, 'category' => ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics']],
        ];
    }

    public function index(Request $request)
    {
        $products = $this->getMockProducts();

        if ($request->category) {
            $products = array_filter($products, fn($p) => $p['category']['slug'] === $request->category);
        }
        if ($request->min_price) {
            $products = array_filter($products, fn($p) => $p['price'] >= $request->min_price);
        }
        if ($request->max_price) {
            $products = array_filter($products, fn($p) => $p['price'] <= $request->max_price);
        }

        return response()->json([
            'data' => array_values($products),
            'meta' => [
                'total' => count($products),
                'per_page' => $request->input('per_page', 12),
                'current_page' => 1,
            ]
        ]);
    }

    public function show(string $slug)
    {
        $products = $this->getMockProducts();
        $product = collect($products)->firstWhere('slug', $slug);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json(['data' => $product]);
    }

    public function featured()
    {
        $products = array_slice($this->getMockProducts(), 0, 4);
        return response()->json(['data' => $products]);
    }

    public function onSale()
    {
        $products = $this->getMockProducts();
        return response()->json(['data' => $products]);
    }

    public function deliveryFriendly()
    {
        $products = $this->getMockProducts();
        return response()->json(['data' => $products]);
    }

    public function search(Request $request)
    {
        $q = strtolower($request->input('q', ''));
        $products = array_filter($this->getMockProducts(), fn($p) =>
            str_contains(strtolower($p['name']), $q) ||
            str_contains(strtolower($p['description']), $q)
        );

        return response()->json(['data' => array_values($products)]);
    }
}
