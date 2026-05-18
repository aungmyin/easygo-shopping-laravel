# Phase 5 — Testing & Security

> **Claude Code instruction:** Read this file and execute every step in order. All tests run using PHPUnit via `php artisan test`. Run the verification command at the end of each step before proceeding.

---

## Step 1 — Configure test environment

**File:** `.env.testing` (create in project root)

```env
APP_ENV=testing
APP_KEY=base64:GENERATE_THIS_BELOW
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_go_shopping_test
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

```bash
# Create the test database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS easy_go_shopping_test;"

# Generate a key for the test env
php artisan key:generate --env=testing

# Run migrations on test database
php artisan migrate --env=testing
```

> ✅ **Verify:** `php artisan migrate:status --env=testing` — all migrations show `Ran`

---

## Step 2 — Configure phpunit.xml

**File:** `phpunit.xml` — make sure these lines exist inside `<php>` section (add if missing):

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_DATABASE" value="easy_go_shopping_test"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_DRIVER" value="sync"/>
```

---

## Step 3 — Create model factories

```bash
php artisan make:factory CategoryFactory --model=Category
php artisan make:factory ProductFactory --model=Product
php artisan make:factory OrderFactory --model=Order
```

**File:** `database/factories/CategoryFactory.php` — replace `definition()`:

```php
public function definition(): array
{
    return [
        'name'        => fake()->words(2, true),
        'slug'        => fake()->unique()->slug(),
        'description' => fake()->sentence(),
        'is_active'   => true,
        'sort_order'  => 0,
    ];
}
```

**File:** `database/factories/ProductFactory.php` — replace `definition()`:

```php
public function definition(): array
{
    return [
        'category_id'          => \App\Models\Category::factory(),
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
```

---

## Step 4 — Create AuthTest

**File:** `tests/Feature/Api/AuthTest.php` (create `tests/Feature/Api/` directory)

```php
<?php
namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->postJson('/api/v1/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['data', 'token']);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email'    => 'nobody@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk()
                 ->assertJsonPath('data.id', $user->id);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/logout');
        $response->assertOk();

        // Token should now be invalid
        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);
    }
}
```

---

## Step 5 — Create ProductApiTest

**File:** `tests/Feature/Api/ProductApiTest.php`

```php
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
                     'data' => [['id', 'name', 'slug', 'price', 'thumbnail_url']],
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

        $response = $this->getJson('/api/v1/products?category=cakes');

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
```

---

## Step 6 — Create CategoryApiTest

**File:** `tests/Feature/Api/CategoryApiTest.php`

```php
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
```

---

## Step 7 — Create AdminApiTest

**File:** `tests/Feature/Api/AdminApiTest.php`

```php
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
                     'total_orders', 'total_products', 'total_users',
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
```

---

## Step 8 — Add rate limiting

**File:** `app/Providers/RouteServiceProvider.php` (or in Laravel 11, `bootstrap/app.php`) — add to the `boot()` method:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(120)->by($request->user()->id)
        : Limit::perMinute(60)->by($request->ip());
});
```

---

## Step 9 — Security checklist — verify each item

Run these checks manually and confirm each passes:

```bash
# 1. APP_DEBUG must be false in production
grep APP_DEBUG .env.example  # should show false

# 2. Check Sanctum token expiration is set (optional but recommended)
grep SANCTUM .env

# 3. Confirm all models have $fillable (not $guarded = [])
grep -r 'guarded' app/Models/

# 4. Confirm soft deletes on Product model
grep SoftDeletes app/Models/Product.php

# 5. List all API routes and verify auth middleware is applied
php artisan route:list --path=api/v1 | grep -v 'auth'
# The only unprotected routes should be: register, login, categories, products
```

---

## Step 10 — Run all tests

```bash
php artisan test
```

> ✅ **Verify:** All tests pass. Example expected output:
> ```
> PASS  Tests\Feature\Api\AuthTest
> PASS  Tests\Feature\Api\ProductApiTest
> PASS  Tests\Feature\Api\CategoryApiTest
> PASS  Tests\Feature\Api\AdminApiTest
> Tests: 22 passed
> ```

---

## Commit

```bash
git add .
git commit -m "test: PHPUnit feature tests for auth, products, categories, admin API and security"
git push
```

---

## Phase 5 complete ✓

Proceed to `phase-6-deployment.md`
