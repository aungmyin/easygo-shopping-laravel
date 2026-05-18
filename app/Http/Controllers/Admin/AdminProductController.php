<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Product, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => Product::with('category')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => Category::active()->get(),
            'product'    => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'category_id'          => 'required|exists:categories,id',
            'price'                => 'required|numeric',
            'stock'                => 'required|integer',
            'description'          => 'nullable',
            'short_description'    => 'nullable',
            'sale_price'           => 'nullable|numeric',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_delivery_friendly' => 'boolean',
            'thumbnail'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Form', [
            'product'    => $product->load('images'),
            'categories' => Category::active()->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'                 => 'required',
            'category_id'          => 'required|exists:categories,id',
            'price'                => 'required|numeric',
            'stock'                => 'required|integer',
            'description'          => 'nullable',
            'sale_price'           => 'nullable|numeric',
            'is_active'            => 'boolean',
            'is_featured'          => 'boolean',
            'is_delivery_friendly' => 'boolean',
            'thumbnail'            => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
            $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
        }

        $data['slug'] = Str::slug($data['name']);
        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
