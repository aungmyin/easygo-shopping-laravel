<?php

namespace Database\Seeders;

use App\Models\{Category, Product};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $cakes = Category::where('slug', 'cakes')->first();
        $sale  = Category::where('slug', 'sale-items')->first();
        $wear  = Category::where('slug', 'wearing-essentials')->first();
        $delivery = Category::where('slug', 'delivery-friendly')->first();
        $gifts = Category::where('slug', 'gift-hampers')->first();

        $products = [
            ['name' => 'Red Velvet Cake',        'category_id' => $cakes->id,    'price' => 350, 'is_featured' => true],
            ['name' => 'Chocolate Fudge Cake',   'category_id' => $cakes->id,    'price' => 420, 'sale_price' => 380],
            ['name' => 'Vanilla Sponge Cake',    'category_id' => $cakes->id,    'price' => 280, 'is_delivery_friendly' => true],
            ['name' => 'Classic White T-Shirt',  'category_id' => $wear->id,     'price' => 199, 'is_featured' => true],
            ['name' => 'Casual Linen Shirt',     'category_id' => $wear->id,     'price' => 450, 'sale_price' => 350],
            ['name' => 'Clearance Tote Bag',     'category_id' => $sale->id,     'price' => 299, 'sale_price' => 99],
            ['name' => 'Premium Gift Hamper',    'category_id' => $gifts->id,    'price' => 999, 'is_featured' => true],
            ['name' => 'Birthday Gift Set',      'category_id' => $gifts->id,    'price' => 599],
            ['name' => 'Packaged Cookie Box',    'category_id' => $delivery->id, 'price' => 180, 'is_delivery_friendly' => true],
            ['name' => 'Dried Fruit Snack Pack', 'category_id' => $delivery->id, 'price' => 120, 'is_delivery_friendly' => true],
        ];

        foreach ($products as $p) {
            Product::create([
                ...$p,
                'slug'       => Str::slug($p['name']),
                'stock'      => 50,
                'is_active'  => true,
                'is_featured'          => $p['is_featured'] ?? false,
                'is_delivery_friendly' => $p['is_delivery_friendly'] ?? false,
            ]);
        }
    }
}
