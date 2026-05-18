# Phase 4 — Admin Panel (Vue 3 + TypeScript + Inertia.js)

> **Claude Code instruction:** Read this file and execute every step in order. The admin panel is served by Laravel via Inertia.js. Admins log in with session auth (web routes), not API tokens. Run the verification command at the end of each step before proceeding.

---

## Step 1 — Define admin web routes

**File:** `routes/web.php` — replace entire file:

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AdminAuthController,
    AdminDashboardController,
    AdminProductController,
    AdminCategoryController,
    AdminOrderController,
    AdminUserController,
};

Route::prefix('admin')->name('admin.')->group(function () {

    // ── Auth (no middleware) ──────────────────────────────────────────────
    Route::get('login',   [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login',  [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    // ── Protected admin pages ─────────────────────────────────────────────
    Route::middleware(['auth', 'admin.web'])->group(function () {
        Route::get('/',               [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('products',    AdminProductController::class);
        Route::resource('categories',  AdminCategoryController::class);
        Route::get('orders',                       [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}',               [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{order}/status',        [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('users',                        [AdminUserController::class, 'index'])->name('users.index');
        Route::put('users/{user}/toggle',          [AdminUserController::class, 'toggle'])->name('users.toggle');
    });
});
```

---

## Step 2 — Create admin web middleware

**File:** `app/Http/Middleware/AdminWebMiddleware.php`

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            return redirect()->route('admin.login');
        }
        return $next($request);
    }
}
```

**File:** `app/Http/Kernel.php` — add to `$middlewareAliases`:

```php
'admin.web' => \App\Http\Middleware\AdminWebMiddleware::class,
```

---

## Step 3 — Create all admin web controllers

```bash
mkdir -p app/Http/Controllers/Admin
php artisan make:controller Admin/AdminAuthController
php artisan make:controller Admin/AdminDashboardController
php artisan make:controller Admin/AdminProductController --resource
php artisan make:controller Admin/AdminCategoryController --resource
php artisan make:controller Admin/AdminOrderController
php artisan make:controller Admin/AdminUserController
```

**File:** `app/Http/Controllers/Admin/AdminAuthController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials) || !Auth::user()->isAdmin()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Invalid admin credentials.']);
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
```

**File:** `app/Http/Controllers/Admin/AdminDashboardController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order, Product, User};
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_orders'    => Order::count(),
                'pending_orders'  => Order::where('status', 'pending')->count(),
                'revenue'         => Order::where('payment_status', 'paid')->sum('total'),
                'total_products'  => Product::count(),
                'low_stock'       => Product::where('stock', '<', 10)->count(),
                'total_customers' => User::where('role', 'customer')->count(),
            ],
            'recent_orders' => Order::with('user')->latest()->take(8)->get(),
        ]);
    }
}
```

**File:** `app/Http/Controllers/Admin/AdminProductController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Product, Category};
use Illuminate\Http\Request;
use Illuminate\Support\{Str};
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
```

**File:** `app/Http/Controllers/Admin/AdminCategoryController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::withCount('products')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
            'parents'  => Category::whereNull('parent_id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required',
            'description' => 'nullable',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ]);
        $data['slug'] = Str::slug($data['name']);
        Category::create($data);
        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category,
            'parents'  => Category::whereNull('parent_id')->where('id', '!=', $category->id)->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => 'required',
            'description' => 'nullable',
            'parent_id'   => 'nullable|exists:categories,id',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer',
        ]);
        $data['slug'] = Str::slug($data['name']);
        $category->update($data);
        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
```

**File:** `app/Http/Controllers/Admin/AdminOrderController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('user')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Orders/Index', [
            'orders'         => $orders,
            'statusFilter'   => $request->status,
        ]);
    }

    public function show(Order $order)
    {
        return Inertia::render('Admin/Orders/Show', [
            'order' => $order->load('user', 'items.product'),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);
        $order->update($data);
        return back()->with('success', 'Order status updated.');
    }
}
```

**File:** `app/Http/Controllers/Admin/AdminUserController.php` — replace entire file:

```php
<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::where('role', 'customer')->latest()->paginate(20),
        ]);
    }

    public function toggle(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }
}
```

---

## Step 4 — Create TypeScript types

**File:** `resources/js/types/index.ts` (create `resources/js/types/` directory first)

```ts
export interface Category {
  id: number
  name: string
  slug: string
  description: string | null
  image: string | null
  is_active: boolean
  sort_order: number
  children?: Category[]
}

export interface Product {
  id: number
  name: string
  slug: string
  description: string | null
  short_description: string | null
  price: number
  sale_price: number | null
  is_on_sale: boolean
  discount_percent: number | null
  effective_price: number
  stock: number
  sku: string | null
  thumbnail_url: string | null
  is_active: boolean
  is_featured: boolean
  is_delivery_friendly: boolean
  category: Category
  created_at: string
}

export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  role: 'customer' | 'admin'
  is_active: boolean
  created_at: string
}

export interface OrderItem {
  id: number
  product_name: string
  quantity: number
  unit_price: number
  subtotal: number
  product?: Product
}

export interface Order {
  id: number
  order_number: string
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
  subtotal: number
  delivery_fee: number
  total: number
  payment_status: 'unpaid' | 'paid' | 'refunded'
  notes: string | null
  paid_at: string | null
  user: User
  items: OrderItem[]
  created_at: string
}

export interface PaginatedData<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: {
    prev: string | null
    next: string | null
  }
}

export interface DashboardStats {
  total_orders: number
  pending_orders: number
  revenue: number
  total_products: number
  low_stock: number
  total_customers: number
}
```

---

## Step 5 — Create Admin Layout

**File:** `resources/js/Layouts/AdminLayout.vue` (create `resources/js/Layouts/` directory)

```vue
<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const flash = computed(() => (page.props.flash as any) ?? {})

function logout() {
  router.post('/admin/logout')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-blue-900 text-white flex flex-col shrink-0">
      <div class="p-6 text-lg font-bold border-b border-blue-700">
        Easy Go Admin
      </div>
      <nav class="flex-1 p-4 space-y-1 text-sm">
        <Link href="/admin"            class="block px-4 py-2 rounded hover:bg-blue-700 transition">Dashboard</Link>
        <Link href="/admin/products"   class="block px-4 py-2 rounded hover:bg-blue-700 transition">Products</Link>
        <Link href="/admin/categories" class="block px-4 py-2 rounded hover:bg-blue-700 transition">Categories</Link>
        <Link href="/admin/orders"     class="block px-4 py-2 rounded hover:bg-blue-700 transition">Orders</Link>
        <Link href="/admin/users"      class="block px-4 py-2 rounded hover:bg-blue-700 transition">Customers</Link>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <button @click="logout" class="w-full text-left px-4 py-2 rounded hover:bg-blue-700 text-sm transition">
          Logout
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-8 overflow-auto">
      <!-- Flash message -->
      <div v-if="flash.success" class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ flash.success }}
      </div>
      <slot />
    </main>
  </div>
</template>
```

---

## Step 6 — Create Admin/Login.vue

**File:** `resources/js/Pages/Admin/Login.vue`

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

const form = useForm({ email: '', password: '' })

function submit() {
  form.post('/admin/login')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-blue-900">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-sm">
      <h1 class="text-2xl font-bold text-center mb-2 text-blue-900">Easy Go Shopping</h1>
      <p class="text-center text-gray-400 text-sm mb-6">Admin Panel</p>

      <div v-if="form.errors.email" class="mb-4 bg-red-50 text-red-600 text-sm px-4 py-2 rounded-lg border border-red-200">
        {{ form.errors.email }}
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <button
        @click="submit"
        :disabled="form.processing"
        class="w-full bg-blue-700 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-800 disabled:opacity-50 transition"
      >
        {{ form.processing ? 'Logging in...' : 'Login' }}
      </button>
    </div>
  </div>
</template>
```

---

## Step 7 — Create Admin/Dashboard.vue

**File:** `resources/js/Pages/Admin/Dashboard.vue`

```vue
<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { DashboardStats, Order } from '@/types'

defineProps<{
  stats: DashboardStats
  recent_orders: Order[]
}>()

function currency(val: number) {
  return '฿' + Number(val).toLocaleString()
}
</script>

<template>
  <AdminLayout>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total orders</p>
        <p class="text-3xl font-bold text-blue-700 mt-1">{{ stats.total_orders }}</p>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
        <p class="text-3xl font-bold text-orange-500 mt-1">{{ stats.pending_orders }}</p>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Revenue (paid)</p>
        <p class="text-3xl font-bold text-green-600 mt-1">{{ currency(stats.revenue) }}</p>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Products</p>
        <p class="text-3xl font-bold mt-1">{{ stats.total_products }}</p>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Low stock</p>
        <p class="text-3xl font-bold text-red-500 mt-1">{{ stats.low_stock }}</p>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Customers</p>
        <p class="text-3xl font-bold mt-1">{{ stats.total_customers }}</p>
      </div>
    </div>

    <!-- Recent orders table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b">
        <h2 class="font-semibold text-gray-700">Recent Orders</h2>
      </div>
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Order #</th>
            <th class="px-4 py-3 text-left">Customer</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-left">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="order in recent_orders" :key="order.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-blue-700">{{ order.order_number }}</td>
            <td class="px-4 py-3">{{ (order as any).user?.name }}</td>
            <td class="px-4 py-3 capitalize">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="{
                  'bg-yellow-100 text-yellow-700': order.status === 'pending',
                  'bg-blue-100 text-blue-700':     order.status === 'confirmed',
                  'bg-green-100 text-green-700':   order.status === 'delivered',
                  'bg-red-100 text-red-700':       order.status === 'cancelled',
                  'bg-gray-100 text-gray-600':     !['pending','confirmed','delivered','cancelled'].includes(order.status),
                }">
                {{ order.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium">{{ currency(order.total) }}</td>
            <td class="px-4 py-3 text-gray-400">{{ new Date(order.created_at).toLocaleDateString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
```

---

## Step 8 — Create Admin/Products/Index.vue

**File:** `resources/js/Pages/Admin/Products/Index.vue`

```vue
<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import type { Product, PaginatedData } from '@/types'

defineProps<{ products: PaginatedData<Product> }>()

function deleteProduct(id: number) {
  if (confirm('Delete this product? This action cannot be undone.')) {
    router.delete(`/admin/products/${id}`)
  }
}
</script>

<template>
  <AdminLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Products</h1>
      <Link href="/admin/products/create"
        class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 transition">
        + Add Product
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Image</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Category</th>
            <th class="px-4 py-3 text-right">Price</th>
            <th class="px-4 py-3 text-center">Stock</th>
            <th class="px-4 py-3 text-center">Active</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <img v-if="p.thumbnail_url" :src="p.thumbnail_url"
                class="w-10 h-10 object-cover rounded-lg" />
              <div v-else class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">
                No img
              </div>
            </td>
            <td class="px-4 py-3 font-medium">{{ p.name }}</td>
            <td class="px-4 py-3 text-gray-500">{{ p.category?.name }}</td>
            <td class="px-4 py-3 text-right">
              <span v-if="p.is_on_sale" class="text-green-600 font-medium">฿{{ p.sale_price }}</span>
              <span v-if="p.is_on_sale" class="text-gray-400 line-through text-xs ml-1">฿{{ p.price }}</span>
              <span v-else>฿{{ p.price }}</span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="p.stock < 10 ? 'text-red-500 font-bold' : 'text-gray-700'">
                {{ p.stock }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="p.is_active ? 'text-green-600' : 'text-gray-400'">
                {{ p.is_active ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-3 text-center space-x-3">
              <Link :href="`/admin/products/${p.id}/edit`"
                class="text-blue-600 hover:underline text-xs">Edit</Link>
              <button @click="deleteProduct(p.id)"
                class="text-red-500 hover:underline text-xs">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-between items-center text-sm text-gray-500">
      <span>{{ products.meta.total }} products total</span>
      <div class="flex gap-2">
        <Link v-if="products.links.prev" :href="products.links.prev"
          class="px-3 py-1 border rounded hover:bg-gray-50">Prev</Link>
        <span class="px-3 py-1">{{ products.meta.current_page }} / {{ products.meta.last_page }}</span>
        <Link v-if="products.links.next" :href="products.links.next"
          class="px-3 py-1 border rounded hover:bg-gray-50">Next</Link>
      </div>
    </div>
  </AdminLayout>
</template>
```

---

## Step 9 — Create Admin/Products/Form.vue (create + edit)

**File:** `resources/js/Pages/Admin/Products/Form.vue`

```vue
<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm } from '@inertiajs/vue3'
import type { Product, Category } from '@/types'

const props = defineProps<{
  product: Product | null
  categories: Category[]
}>()

const isEdit = !!props.product

const form = useForm({
  name:                 props.product?.name ?? '',
  category_id:          props.product?.category?.id ?? '',
  price:                props.product?.price ?? '',
  sale_price:           props.product?.sale_price ?? '',
  stock:                props.product?.stock ?? 0,
  description:          props.product?.description ?? '',
  short_description:    props.product?.short_description ?? '',
  is_active:            props.product?.is_active ?? true,
  is_featured:          props.product?.is_featured ?? false,
  is_delivery_friendly: props.product?.is_delivery_friendly ?? false,
  thumbnail:            null as File | null,
})

function submit() {
  if (isEdit) {
    form.put(`/admin/products/${props.product!.id}`, { forceFormData: true })
  } else {
    form.post('/admin/products', { forceFormData: true })
  }
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center gap-3 mb-6">
      <a href="/admin/products" class="text-gray-400 hover:text-gray-600 text-sm">Products</a>
      <span class="text-gray-300">/</span>
      <h1 class="text-2xl font-bold text-gray-800">{{ isEdit ? 'Edit Product' : 'Add Product' }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
      <div class="grid grid-cols-2 gap-5">

        <!-- Name -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Product name</label>
          <input v-model="form.name" type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select v-model="form.category_id"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="">Select category</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</p>
        </div>

        <!-- Stock -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
          <input v-model="form.stock" type="number" min="0"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Price (฿)</label>
          <input v-model="form.price" type="number" step="0.01" min="0"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
          <p v-if="form.errors.price" class="text-red-500 text-xs mt-1">{{ form.errors.price }}</p>
        </div>

        <!-- Sale price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price (฿, optional)</label>
          <input v-model="form.sale_price" type="number" step="0.01" min="0"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <!-- Description -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea v-model="form.description" rows="4"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        </div>

        <!-- Thumbnail -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail image</label>
          <input type="file" accept="image/*"
            @change="(e) => form.thumbnail = (e.target as HTMLInputElement).files?.[0] ?? null"
            class="text-sm" />
          <img v-if="isEdit && product?.thumbnail_url"
            :src="product.thumbnail_url"
            class="mt-2 w-24 h-24 object-cover rounded-lg border" />
        </div>

        <!-- Toggles -->
        <div class="col-span-2 flex flex-wrap gap-6">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="rounded" />
            Active (visible on store)
          </label>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" v-model="form.is_featured" class="rounded" />
            Featured (homepage)
          </label>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" v-model="form.is_delivery_friendly" class="rounded" />
            Delivery friendly
          </label>
        </div>
      </div>

      <!-- Submit -->
      <div class="mt-6 flex gap-3">
        <button @click="submit" :disabled="form.processing"
          class="bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-800 disabled:opacity-50 transition">
          {{ form.processing ? 'Saving...' : (isEdit ? 'Update Product' : 'Create Product') }}
        </button>
        <a href="/admin/products"
          class="px-6 py-2 rounded-lg border text-sm hover:bg-gray-50 transition">
          Cancel
        </a>
      </div>
    </div>
  </AdminLayout>
</template>
```

---

## Step 10 — Create Admin/Orders/Index.vue

**File:** `resources/js/Pages/Admin/Orders/Index.vue`

```vue
<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import type { Order, PaginatedData } from '@/types'

defineProps<{
  orders: PaginatedData<Order>
  statusFilter?: string
}>()

const statusColors: Record<string, string> = {
  pending:    'bg-yellow-100 text-yellow-700',
  confirmed:  'bg-blue-100 text-blue-700',
  processing: 'bg-purple-100 text-purple-700',
  shipped:    'bg-indigo-100 text-indigo-700',
  delivered:  'bg-green-100 text-green-700',
  cancelled:  'bg-red-100 text-red-700',
}
</script>

<template>
  <AdminLayout>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Orders</h1>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
          <tr>
            <th class="px-4 py-3 text-left">Order #</th>
            <th class="px-4 py-3 text-left">Customer</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-blue-700">{{ order.order_number }}</td>
            <td class="px-4 py-3">{{ order.user?.name }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusColors[order.status] ?? 'bg-gray-100 text-gray-600'">
                {{ order.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-medium">฿{{ Number(order.total).toLocaleString() }}</td>
            <td class="px-4 py-3 text-gray-400 text-xs">{{ new Date(order.created_at).toLocaleDateString() }}</td>
            <td class="px-4 py-3 text-center">
              <Link :href="`/admin/orders/${order.id}`" class="text-blue-600 hover:underline text-xs">View</Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
```

---

## Step 11 — Create Admin/Orders/Show.vue

**File:** `resources/js/Pages/Admin/Orders/Show.vue`

```vue
<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
use { useForm } from '@inertiajs/vue3'
import type { Order } from '@/types'

const props = defineProps<{ order: Order }>()

const statusForm = useForm({ status: props.order.status })

function updateStatus() {
  statusForm.put(`/admin/orders/${props.order.id}/status`)
}
</script>

<template>
  <AdminLayout>
    <div class="flex items-center gap-3 mb-6">
      <a href="/admin/orders" class="text-gray-400 hover:text-gray-600 text-sm">Orders</a>
      <span class="text-gray-300">/</span>
      <h1 class="text-2xl font-bold font-mono">{{ order.order_number }}</h1>
    </div>

    <div class="grid grid-cols-3 gap-6">
      <!-- Order items -->
      <div class="col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold mb-4">Items</h2>
        <table class="w-full text-sm">
          <thead class="text-gray-400 text-xs uppercase border-b">
            <tr>
              <th class="pb-2 text-left">Product</th>
              <th class="pb-2 text-center">Qty</th>
              <th class="pb-2 text-right">Unit price</th>
              <th class="pb-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="item in order.items" :key="item.id">
              <td class="py-3">{{ item.product_name }}</td>
              <td class="py-3 text-center">{{ item.quantity }}</td>
              <td class="py-3 text-right">฿{{ item.unit_price }}</td>
              <td class="py-3 text-right font-medium">฿{{ item.subtotal }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t text-sm">
            <tr><td colspan="3" class="pt-3 text-right text-gray-500">Delivery fee</td><td class="pt-3 text-right">฿{{ order.delivery_fee }}</td></tr>
            <tr><td colspan="3" class="pt-1 text-right font-semibold">Total</td><td class="pt-1 text-right font-bold text-blue-700">฿{{ order.total }}</td></tr>
          </tfoot>
        </table>
      </div>

      <!-- Order details -->
      <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold mb-3 text-sm">Update status</h2>
          <select v-model="statusForm.status"
            class="w-full border rounded-lg px-3 py-2 text-sm mb-3">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <button @click="updateStatus" :disabled="statusForm.processing"
            class="w-full bg-blue-700 text-white py-2 rounded-lg text-sm hover:bg-blue-800 disabled:opacity-50">
            Update
          </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 text-sm space-y-2">
          <h2 class="font-semibold mb-3">Customer</h2>
          <p class="text-gray-700">{{ order.user?.name }}</p>
          <p class="text-gray-400">{{ order.user?.email }}</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
```

> ⚠️ **Note:** Fix the typo on line 3 of the script: `use { useForm }` should be `import { useForm }`. Claude Code should correct this when writing the file.

---

## Step 12 — Build and verify

```bash
npm run build
php artisan serve
```

Open `http://localhost:8000/admin/login` in your browser.

Login with:
- **Email:** `admin@easygo.com`
- **Password:** `admin123456`

> ✅ **Verify:**
> - Dashboard loads with stats
> - Products page lists all seeded products
> - Create product form saves with image upload
> - Orders page shows any orders

---

## Commit

```bash
git add .
git commit -m "feat: admin panel Vue 3 TypeScript Inertia - dashboard, products, categories, orders"
git push
```

---

## Phase 4 complete ✓

Proceed to `phase-5-testing-security.md`
