<?php

namespace Tests\Feature\Api;

use App\Models\{User, Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::factory()->create(['role' => 'admin']);
        return $admin->createToken('test')->plainTextToken;
    }

    private function customerToken(): string
    {
        $user = User::factory()->create(['role' => 'customer']);
        return $user->createToken('test')->plainTextToken;
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->withToken($this->adminToken())
                         ->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
                 ->assertJsonStructure(['data' => [
                     'total_orders', 'total_products', 'total_customers',
                 ]]);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $response = $this->withToken($this->customerToken())
                         ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_admin_can_create_product(): void
    {
        $cat = Category::factory()->create();

        $response = $this->withToken($this->adminToken())
                         ->postJson('/api/v1/admin/products', [
                             'name'        => 'Test Product',
                             'category_id' => $cat->id,
                             'price'       => 299.00,
                             'stock'       => 20,
                             'is_active'   => true,
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Test Product');

        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    public function test_admin_can_soft_delete_product(): void
    {
        $cat     = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id]);

        $response = $this->withToken($this->adminToken())
                         ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
