<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wearing Essentials', 'description' => 'Clothing and accessories for everyday wear'],
            ['name' => 'Gift Hampers',        'description' => 'Curated gift sets and bundles for every occasion'],
            ['name' => 'Cakes',               'description' => 'Bakery items and custom cake orders'],
            ['name' => 'Sale Items',          'description' => 'Discounted and clearance products'],
            ['name' => 'Delivery Friendly',   'description' => 'Products optimised for safe delivery'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                ...$cat,
                'slug'      => Str::slug($cat['name']),
                'is_active' => true,
            ]);
        }
    }
}
