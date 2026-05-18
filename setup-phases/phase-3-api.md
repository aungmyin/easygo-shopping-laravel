# Phase 3 — REST API Development

> **Claude Code instruction:** Read this file and execute every step in order. All API routes are under `/api/v1/`. All responses are JSON. Run the verification command at the end of each step before proceeding.

---

## Step 1 — Define all API routes

**File:** `routes/api.php` — replace entire file:

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    CategoryController,
    ProductController,
    CartController,
    OrderController,
};
use App\Http\Controllers\Api\V1\Admin\{
    AdminProductController,
    AdminOrderController,
    AdminDashboardController,
    AdminCategoryController,
    AdminUserController,
};

Route::prefix('v1')->group(function () {

    // ── Public auth ──────────────────────────────────────────────────────
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    // ── Public categories ─────────────────────────────────────────────────
    Route::get('categories',                [CategoryController::class, 'index']);
    Route::get('categories/{slug}',         [CategoryController::class, 'show']);
    Route::get('categories/{slug}/products',[CategoryController::class, 'products']);

    // ── Public products ───────────────────────────────────────────────────
    Route::get('products',                  [ProductController::class, 'index']);
    Route::get('products/featured',         [ProductController::class, 'featured']);
    Route::get('products/sale',             [ProductController::class, 'onSale']);
    Route::get('products/delivery-friendly',[ProductController::class, 'deliveryFriendly']);
    Route::get('products/search',           [ProductController::class, 'search']);
    Route::get('products/{slug}',           [ProductController::class, 'show']);

    // ── Authenticated customer routes ─────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
        Route::put('me',      [AuthController::class, 'update']);

        Route::prefix('cart')->group(function () {
            Route::get('/',              [CartController::class, 'index']);
            Route::post('/items',        [CartController::class, 'addItem']);
            Route::put('/items/{id}',    [CartController::class, 'updateItem']);
            Route::delete('/items/{id}', [CartController::class, 'removeItem']);
            Route::delete('/',           [CartController::class, 'clear']);
        });

        Route::get('orders',                  [OrderController::class, 'index']);
        Route::post('orders',                 [OrderController::class, 'store']);
        Route::get('orders/{number}',         [OrderController::class, 'show']);
        Route::post('orders/{number}/cancel', [OrderController::class, 'cancel']);
    });

    // ── Admin routes ──────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('dashboard',                 [AdminDashboardController::class, 'index']);
        Route::apiResource('products',           AdminProductController::class);
        Route::apiResource('categories',         AdminCategoryController::class);
        Route::get('orders',                    [AdminOrderController::class, 'index']);
        Route::get('orders/{id}',               [AdminOrderController::class, 'show']);
        Route::put('orders/{id}/status',        [AdminOrderController::class, 'updateStatus']);
        Route::get('users',                     [AdminUserController::class, 'index']);
        Route::put('users/{id}/toggle-active',  [AdminUserController::class, 'toggleActive']);
    });
});
```

---

## Step 2 — Create admin API middleware

**File:** `app/Http/Middleware/AdminMiddleware.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }
        return $next($request);
    }
}
```

**File:** `app/Http/Kernel.php` — add to `$middlewareAliases`:

```php
'admin' => \App\Http\Middleware\AdminMiddleware::class,
```

---

## Step 3 — Create AuthController

```bash
php artisan make:controller Api/V1/AuthController
```

**File:** `app/Http/Controllers/Api/V1/AuthController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user  = User::create($data);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data'    => new UserResource($user),
            'token'   => $token,
            'message' => 'Registered successfully',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $user  = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data'    => new UserResource($user),
            'token'   => $token,
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => 'sometimes|string',
            'phone' => 'nullable|string',
        ]);
        $request->user()->update($data);
        return response()->json([
            'data'    => new UserResource($request->user()),
            'message' => 'Profile updated',
        ]);
    }
}
```

---

## Step 4 — Create all API Resources

```bash
php artisan make:resource UserResource
php artisan make:resource CategoryResource
php artisan make:resource ProductResource
php artisan make:resource ProductImageResource
php artisan make:resource OrderResource
php artisan make:resource OrderItemResource
```

**File:** `app/Http/Resources/UserResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'         => $this->id,
        'name'       => $this->name,
        'email'      => $this->email,
        'phone'      => $this->phone,
        'role'       => $this->role,
        'is_active'  => $this->is_active,
        'created_at' => $this->created_at->toIso8601String(),
    ];
}
```

**File:** `app/Http/Resources/CategoryResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'          => $this->id,
        'name'        => $this->name,
        'slug'        => $this->slug,
        'description' => $this->description,
        'image'       => $this->image ? \Storage::url($this->image) : null,
        'is_active'   => $this->is_active,
        'sort_order'  => $this->sort_order,
        'children'    => CategoryResource::collection($this->whenLoaded('children')),
    ];
}
```

**File:** `app/Http/Resources/ProductResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'                   => $this->id,
        'name'                 => $this->name,
        'slug'                 => $this->slug,
        'description'          => $this->description,
        'short_description'    => $this->short_description,
        'price'                => (float) $this->price,
        'sale_price'           => $this->sale_price ? (float) $this->sale_price : null,
        'is_on_sale'           => $this->is_on_sale,
        'discount_percent'     => $this->discount_percent,
        'effective_price'      => (float) $this->effective_price,
        'stock'                => $this->stock,
        'sku'                  => $this->sku,
        'thumbnail_url'        => $this->thumbnail_url,
        'is_featured'          => $this->is_featured,
        'is_delivery_friendly' => $this->is_delivery_friendly,
        'category'             => new CategoryResource($this->whenLoaded('category')),
        'images'               => ProductImageResource::collection($this->whenLoaded('images')),
        'created_at'           => $this->created_at->toIso8601String(),
    ];
}
```

**File:** `app/Http/Resources/ProductImageResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'         => $this->id,
        'url'        => $this->url,
        'alt_text'   => $this->alt_text,
        'sort_order' => $this->sort_order,
    ];
}
```

**File:** `app/Http/Resources/OrderResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'             => $this->id,
        'order_number'   => $this->order_number,
        'status'         => $this->status,
        'subtotal'       => (float) $this->subtotal,
        'delivery_fee'   => (float) $this->delivery_fee,
        'total'          => (float) $this->total,
        'payment_status' => $this->payment_status,
        'notes'          => $this->notes,
        'paid_at'        => $this->paid_at?->toIso8601String(),
        'user'           => new UserResource($this->whenLoaded('user')),
        'items'          => OrderItemResource::collection($this->whenLoaded('items')),
        'created_at'     => $this->created_at->toIso8601String(),
    ];
}
```

**File:** `app/Http/Resources/OrderItemResource.php` — replace `toArray()`:

```php
public function toArray($request): array
{
    return [
        'id'           => $this->id,
        'product_name' => $this->product_name,
        'quantity'     => $this->quantity,
        'unit_price'   => (float) $this->unit_price,
        'subtotal'     => (float) $this->subtotal,
        'product'      => new ProductResource($this->whenLoaded('product')),
    ];
}
```

---

## Step 5 — Create CategoryController

```bash
php artisan make:controller Api/V1/CategoryController
```

**File:** `app/Http/Controllers/Api/V1/CategoryController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\{CategoryResource, ProductResource};

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::active()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(string $slug)
    {
        $category = Category::where('slug', $slug)
            ->with('children')
            ->firstOrFail();

        return new CategoryResource($category);
    }

    public function products(string $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with('category', 'images')
            ->paginate(request('per_page', 12));

        return ProductResource::collection($products);
    }
}
```

---

## Step 6 — Create ProductController

```bash
php artisan make:controller Api/V1/ProductController
```

**File:** `app/Http/Controllers/Api/V1/ProductController.php` — replace entire file:

```php
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
```

---

## Step 7 — Create CartController

```bash
php artisan make:controller Api/V1/CartController
```

**File:** `app/Http/Controllers/Api/V1/CartController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart  = session('cart.' . $request->user()->id, []);
        $items = [];
        $total = 0;

        foreach ($cart as $id => $qty) {
            $product = Product::find($id);
            if (!$product) continue;
            $sub    = $product->effective_price * $qty;
            $total += $sub;
            $items[] = [
                'product_id'    => $id,
                'name'          => $product->name,
                'quantity'      => $qty,
                'unit_price'    => $product->effective_price,
                'subtotal'      => $sub,
                'thumbnail_url' => $product->thumbnail_url,
            ];
        }

        return response()->json([
            'data' => ['items' => $items, 'total' => round($total, 2)],
        ]);
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $key = 'cart.' . $request->user()->id . '.' . $data['product_id'];
        session([$key => session($key, 0) + $data['quantity']]);

        return response()->json(['message' => 'Item added to cart']);
    }

    public function updateItem(Request $request, $productId)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1']);
        session(['cart.' . $request->user()->id . '.' . $productId => $data['quantity']]);
        return response()->json(['message' => 'Cart updated']);
    }

    public function removeItem(Request $request, $productId)
    {
        $cart = session('cart.' . $request->user()->id, []);
        unset($cart[$productId]);
        session(['cart.' . $request->user()->id => $cart]);
        return response()->json(['message' => 'Item removed']);
    }

    public function clear(Request $request)
    {
        session()->forget('cart.' . $request->user()->id);
        return response()->json(['message' => 'Cart cleared']);
    }
}
```

---

## Step 8 — Create OrderController

```bash
php artisan make:controller Api/V1/OrderController
```

**File:** `app/Http/Controllers/Api/V1/OrderController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\{Order, Product};
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $number)
    {
        $order = $request->user()
            ->orders()
            ->with('items.product')
            ->where('order_number', $number)
            ->firstOrFail();

        return new OrderResource($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'delivery_address' => 'required|string',
            'notes'            => 'nullable|string',
        ]);

        $cart = session('cart.' . $request->user()->id, []);
        if (empty($cart)) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        return DB::transaction(function () use ($request, $cart) {
            $subtotal = 0;
            $items    = [];

            foreach ($cart as $productId => $qty) {
                $product = Product::lockForUpdate()->findOrFail($productId);

                if ($product->stock < $qty) {
                    return response()->json([
                        'message' => "Insufficient stock for {$product->name}",
                    ], 422);
                }

                $sub       = $product->effective_price * $qty;
                $subtotal += $sub;
                $items[]   = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $qty,
                    'unit_price'   => $product->effective_price,
                    'subtotal'     => $sub,
                ];

                $product->decrement('stock', $qty);
            }

            $fee   = $subtotal >= 500 ? 0 : 50;
            $order = $request->user()->orders()->create([
                'subtotal'                   => $subtotal,
                'delivery_fee'               => $fee,
                'total'                      => $subtotal + $fee,
                'delivery_address_snapshot'  => $request->delivery_address,
                'notes'                      => $request->notes,
            ]);

            $order->items()->createMany($items);
            session()->forget('cart.' . $request->user()->id);

            return response()->json([
                'data'    => new OrderResource($order->load('items')),
                'message' => 'Order placed successfully',
            ], 201);
        });
    }

    public function cancel(Request $request, string $number)
    {
        $order = $request->user()
            ->orders()
            ->where('order_number', $number)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be cancelled'], 422);
        }

        $order->update(['status' => 'cancelled']);
        return response()->json(['message' => 'Order cancelled']);
    }
}
```

---

## Step 9 — Create Admin API controllers

```bash
mkdir -p app/Http/Controllers/Api/V1/Admin
php artisan make:controller Api/V1/Admin/AdminDashboardController
php artisan make:controller Api/V1/Admin/AdminProductController --api
php artisan make:controller Api/V1/Admin/AdminCategoryController --api
php artisan make:controller Api/V1/Admin/AdminOrderController
php artisan make:controller Api/V1/Admin/AdminUserController
```

**File:** `app/Http/Controllers/Api/V1/Admin/AdminDashboardController.php` — replace `index()`:

```php
public function index()
{
    return response()->json(['data' => [
        'total_orders'   => \App\Models\Order::count(),
        'pending_orders' => \App\Models\Order::where('status', 'pending')->count(),
        'total_revenue'  => \App\Models\Order::where('payment_status', 'paid')->sum('total'),
        'total_products' => \App\Models\Product::count(),
        'low_stock'      => \App\Models\Product::where('stock', '<', 10)->count(),
        'total_users'    => \App\Models\User::where('role', 'customer')->count(),
        'new_users_30d'  => \App\Models\User::where('role', 'customer')
                                ->where('created_at', '>=', now()->subDays(30))->count(),
        'recent_orders'  => \App\Models\Order::with('user')->latest()->take(10)->get(),
    ]]);
}
```

**File:** `app/Http/Controllers/Api/V1/Admin/AdminProductController.php` — replace all methods:

```php
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

public function index()
{
    $products = Product::withTrashed()
        ->with('category')
        ->when(request('search'), fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
        ->latest()
        ->paginate(20);

    return ProductResource::collection($products);
}

public function store(Request $request)
{
    $data = $request->validate([
        'name'                 => 'required',
        'category_id'          => 'required|exists:categories,id',
        'price'                => 'required|numeric|min:0',
        'sale_price'           => 'nullable|numeric',
        'stock'                => 'required|integer',
        'description'          => 'nullable|string',
        'short_description'    => 'nullable|string',
        'is_active'            => 'boolean',
        'is_featured'          => 'boolean',
        'is_delivery_friendly' => 'boolean',
        'thumbnail'            => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('thumbnail')) {
        $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
    }

    $data['slug'] = Str::slug($data['name']);
    $product = Product::create($data);

    return new ProductResource($product->load('category'));
}

public function show(Product $product)
{
    return new ProductResource($product->load('category', 'images'));
}

public function update(Request $request, Product $product)
{
    $data = $request->validate([
        'name'                 => 'sometimes',
        'category_id'          => 'sometimes|exists:categories,id',
        'price'                => 'sometimes|numeric',
        'sale_price'           => 'nullable|numeric',
        'stock'                => 'sometimes|integer',
        'description'          => 'nullable|string',
        'is_active'            => 'boolean',
        'is_featured'          => 'boolean',
        'is_delivery_friendly' => 'boolean',
        'thumbnail'            => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('thumbnail')) {
        if ($product->thumbnail) Storage::disk('public')->delete($product->thumbnail);
        $data['thumbnail'] = $request->file('thumbnail')->store('products', 'public');
    }

    if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
    $product->update($data);

    return new ProductResource($product->fresh('category'));
}

public function destroy(Product $product)
{
    $product->delete();
    return response()->json(['message' => 'Product deleted']);
}
```

**File:** `app/Http/Controllers/Api/V1/Admin/AdminOrderController.php` — replace all methods:

```php
use App\Models\Order;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

public function index(Request $request)
{
    $orders = Order::with('user', 'items')
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->when($request->search, fn($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
        ->latest()
        ->paginate(20);

    return OrderResource::collection($orders);
}

public function show(Order $order)
{
    return new OrderResource($order->load('user', 'items.product'));
}

public function updateStatus(Request $request, Order $order)
{
    $data = $request->validate([
        'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
    ]);
    $order->update($data);
    return response()->json(['message' => 'Status updated', 'status' => $order->status]);
}
```

---

## Step 10 — Test all API endpoints

```bash
php artisan serve &

# Test public endpoints
curl -s http://localhost:8000/api/v1/categories | python3 -m json.tool | head -20
curl -s http://localhost:8000/api/v1/products | python3 -m json.tool | head -20
curl -s http://localhost:8000/api/v1/products/featured | python3 -m json.tool

# Test login and get token
curl -s -X POST http://localhost:8000/api/v1/login \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"email":"admin@easygo.com","password":"admin123456"}'
```

> ✅ **Verify:**
> - `/api/v1/categories` returns 5 categories with slugs
> - `/api/v1/products` returns paginated products with `data` and `meta`
> - `/api/v1/login` returns a `token` field

---

## Commit

```bash
git add .
git commit -m "feat: full REST API with auth, products, cart, orders, and admin endpoints"
git push
```

---

## Phase 3 complete ✓

Proceed to `phase-4-admin-panel.md`
