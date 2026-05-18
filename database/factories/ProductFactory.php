<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id'          => Category::factory(),
            'name'                 => fake()->words(3, true),
            'slug'                 => fake()->unique()->slug(),
            'description'          => fake()->paragraph(),
            'price'                => fake()->randomFloat(2, 50, 2000),
            'sale_price'           => null,
            'stock'                => fake()->numberBetween(5, 100),
            'is_active'            => true,
            'is_featured'          => false,
            'is_delivery_friendly' => false,
        ];
    }

    public function onSale(): static
    {
        return $this->state(fn(array $attrs) => [
            'sale_price' => round($attrs['price'] * 0.8, 2),
        ]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
