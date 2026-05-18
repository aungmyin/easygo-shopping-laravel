<?php

namespace Tests\Feature\Api;

use App\Models\{Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_categories(): void
    {
        Category::factory(3)->create(['is_active' => true]);
        Category::factory(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_category(): void
    {
        $category = Category::factory()->create(['slug' => 'cakes', 'is_active' => true]);

        $response = $this->getJson('/api/v1/categories/cakes');

        $response->assertOk()->assertJsonPath('data.id', $category->id);
    }

    public function test_can_get_products_in_category(): void
    {
        $cat = Category::factory()->create(['slug' => 'gift-hampers']);
        Product::factory(4)->create(['category_id' => $cat->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/categories/gift-hampers/products');

        $response->assertOk()->assertJsonCount(4, 'data');
    }
}
