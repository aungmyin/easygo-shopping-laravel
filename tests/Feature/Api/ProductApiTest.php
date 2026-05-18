<?php

namespace Tests\Feature\Api;

use App\Models\{Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        $category = Category::factory()->create();
        Product::factory(5)->create(['category_id' => $category->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [['id', 'name', 'slug', 'price']],
                     'meta' => ['total', 'per_page', 'current_page', 'last_page'],
                 ]);
    }

    public function test_inactive_products_are_hidden(): void
    {
        $cat = Category::factory()->create();
        Product::factory(3)->create(['category_id' => $cat->id, 'is_active' => true]);
        Product::factory(2)->create(['category_id' => $cat->id, 'is_active' => false]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_product_by_slug(): void
    {
        $cat     = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products/' . $product->slug);

        $response->assertOk()
                 ->assertJsonPath('data.id', $product->id)
                 ->assertJsonPath('data.name', $product->name);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/does-not-exist');
        $response->assertStatus(404);
    }

    public function test_can_filter_products_by_category(): void
    {
        $cakes    = Category::factory()->create(['slug' => 'cakes']);
        $clothing = Category::factory()->create(['slug' => 'clothing']);

        Product::factory(3)->create(['category_id' => $cakes->id, 'is_active' => true]);
        Product::factory(2)->create(['category_id' => $clothing->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/categories/cakes/products');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_featured_products_endpoint(): void
    {
        $cat = Category::factory()->create();
        Product::factory(3)->featured()->create(['category_id' => $cat->id, 'is_active' => true]);
        Product::factory(2)->create(['category_id' => $cat->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products/featured');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_on_sale_products_endpoint(): void
    {
        $cat = Category::factory()->create();
        Product::factory(4)->onSale()->create(['category_id' => $cat->id, 'is_active' => true]);
        Product::factory(2)->create(['category_id' => $cat->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products/sale');

        $response->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_can_search_products(): void
    {
        $cat = Category::factory()->create();
        Product::factory()->create(['category_id' => $cat->id, 'is_active' => true, 'name' => 'Red Velvet Cake']);
        Product::factory()->create(['category_id' => $cat->id, 'is_active' => true, 'name' => 'Chocolate Brownie']);

        $response = $this->getJson('/api/v1/products/search?q=velvet');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_search_requires_minimum_2_chars(): void
    {
        $response = $this->getJson('/api/v1/products/search?q=a');
        $response->assertStatus(422);
    }
}
