# Phase 2 — Database Migrations, Models & Seeders

> **Claude Code instruction:** Read this file and execute every step in order. All steps run from the project root `~/projects/easy-go-shopping`. Run the verification command at the end of each step before proceeding.

---

## Step 1 — Add role/phone/is_active to users table

```bash
php artisan make:migration add_role_to_users_table --table=users
```

**File:** `database/migrations/xxxx_add_role_to_users_table.php` — set `up()` and `down()`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['customer', 'admin'])->default('customer')->after('email');
        $table->string('phone')->nullable()->after('role');
        $table->boolean('is_active')->default(true)->after('phone');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['role', 'phone', 'is_active']);
    });
}
```

---

## Step 2 — Create categories migration

```bash
php artisan make:migration create_categories_table
```

**File:** `database/migrations/xxxx_create_categories_table.php` — set `up()`:

```php
public function up(): void
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('image')->nullable();
        $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
}
```

---

## Step 3 — Create products migration

```bash
php artisan make:migration create_products_table
```

**File:** `database/migrations/xxxx_create_products_table.php` — set `up()`:

```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->text('short_description')->nullable();
        $table->decimal('price', 10, 2);
        $table->decimal('sale_price', 10, 2)->nullable();
        $table->integer('stock')->default(0);
        $table->string('sku')->unique()->nullable();
        $table->string('thumbnail')->nullable();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_featured')->default(false);
        $table->boolean('is_delivery_friendly')->default(false);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
        $table->softDeletes();
    });
}
```

---

## Step 4 — Create product_images migration

```bash
php artisan make:migration create_product_images_table
```

**File:** set `up()`:

```php
public function up(): void
{
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->string('path');
        $table->string('alt_text')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
}
```

---

## Step 5 — Create addresses migration

```bash
php artisan make:migration create_addresses_table
```

**File:** set `up()`:

```php
public function up(): void
{
    Schema::create('addresses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('label')->default('Home');
        $table->string('recipient_name');
        $table->string('phone');
        $table->string('address_line1');
        $table->string('address_line2')->nullable();
        $table->string('city');
        $table->string('state')->nullable();
        $table->string('postal_code')->nullable();
        $table->string('country')->default('TH');
        $table->boolean('is_default')->default(false);
        $table->timestamps();
    });
}
```

---

## Step 6 — Create orders migration

```bash
php artisan make:migration create_orders_table
```

**File:** set `up()`:

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained();
        $table->string('order_number')->unique();
        $table->enum('status', ['pending','confirmed','processing','shipped','delivered','cancelled'])
              ->default('pending');
        $table->decimal('subtotal', 10, 2);
        $table->decimal('delivery_fee', 10, 2)->default(0);
        $table->decimal('discount_amount', 10, 2)->default(0);
        $table->decimal('total', 10, 2);
        $table->string('delivery_address_snapshot', 1000);
        $table->string('payment_method')->nullable();
        $table->enum('payment_status', ['unpaid','paid','refunded'])->default('unpaid');
        $table->text('notes')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });
}
```

---

## Step 7 — Create order_items migration

```bash
php artisan make:migration create_order_items_table
```

**File:** set `up()`:

```php
public function up(): void
{
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained();
        $table->string('product_name');
        $table->integer('quantity');
        $table->decimal('unit_price', 10, 2);
        $table->decimal('subtotal', 10, 2);
        $table->timestamps();
    });
}
```

---

## Step 8 — Run all migrations

```bash
php artisan migrate
```

> ✅ **Verify:** `php artisan migrate:status` — all migrations show `Ran`

---

## Step 9 — Create Category model

**File:** `app/Models/Category.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsTo};

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'image',
        'parent_id', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function parent(): BelongsTo { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Category::class, 'parent_id'); }
    public function scopeActive($q)     { return $q->where('is_active', true); }
}
```

---

## Step 10 — Create Product model

**File:** `app/Models/Product.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'short_description',
        'price', 'sale_price', 'stock', 'sku', 'thumbnail',
        'is_active', 'is_featured', 'is_delivery_friendly', 'sort_order',
    ];

    protected $casts = [
        'price'                => 'decimal:2',
        'sale_price'           => 'decimal:2',
        'is_active'            => 'boolean',
        'is_featured'          => 'boolean',
        'is_delivery_friendly' => 'boolean',
    ];

    public function category(): BelongsTo  { return $this->belongsTo(Category::class); }
    public function images(): HasMany      { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function orderItems(): HasMany  { return $this->hasMany(OrderItem::class); }

    public function scopeActive($q)            { return $q->where('is_active', true); }
    public function scopeFeatured($q)          { return $q->where('is_featured', true); }
    public function scopeOnSale($q)            { return $q->whereNotNull('sale_price'); }
    public function scopeDeliveryFriendly($q)  { return $q->where('is_delivery_friendly', true); }

    public function getIsOnSaleAttribute(): bool
    {
        return !is_null($this->sale_price);
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (!$this->is_on_sale) return null;
        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? Storage::url($this->thumbnail) : null;
    }
}
```

---

## Step 11 — Create ProductImage model

**File:** `app/Models/ProductImage.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'alt_text', 'sort_order'];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
```

---

## Step 12 — Update User model

**File:** `app/Models/User.php` — replace entire file:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    public function orders(): HasMany    { return $this->hasMany(Order::class); }
    public function addresses(): HasMany { return $this->hasMany(Address::class); }
    public function isAdmin(): bool      { return $this->role === 'admin'; }
}
```

---

## Step 13 — Create Order model

**File:** `app/Models/Order.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'delivery_fee',
        'discount_amount', 'total', 'delivery_address_snapshot',
        'payment_method', 'payment_status', 'notes', 'paid_at',
    ];

    protected $casts = [
        'paid_at'   => 'datetime',
        'subtotal'  => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function items(): HasMany   { return $this->hasMany(OrderItem::class); }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            $order->order_number = 'EGS-' . strtoupper(substr(uniqid(), 5));
        });
    }
}
```

---

## Step 14 — Create OrderItem model

**File:** `app/Models/OrderItem.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name',
        'quantity', 'unit_price', 'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function order(): BelongsTo   { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
```

---

## Step 15 — Create Address model

**File:** `app/Models/Address.php`

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'label', 'recipient_name', 'phone',
        'address_line1', 'address_line2', 'city',
        'state', 'postal_code', 'country', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
```

---

## Step 16 — Create CategorySeeder

**File:** `database/seeders/CategorySeeder.php`

```php
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
```

---

## Step 17 — Create ProductSeeder

**File:** `database/seeders/ProductSeeder.php`

```php
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
```

---

## Step 18 — Create AdminSeeder

**File:** `database/seeders/AdminSeeder.php`

```php
<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'      => 'Admin',
            'email'     => 'admin@easygo.com',
            'password'  => bcrypt('admin123456'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
```

---

## Step 19 — Register all seeders in DatabaseSeeder

**File:** `database/seeders/DatabaseSeeder.php` — replace `run()`:

```php
public function run(): void
{
    $this->call([
        CategorySeeder::class,
        ProductSeeder::class,
        AdminSeeder::class,
    ]);
}
```

---

## Step 20 — Run seeders

```bash
php artisan db:seed
```

> ✅ **Verify:**
> ```bash
> php artisan tinker --execute="echo Category::count() . ' categories, ' . Product::count() . ' products';"
> # Expected: 5 categories, 10 products
> ```

---

## Step 21 — Create storage symlink

```bash
php artisan storage:link
```

> ✅ **Verify:** `ls -la public/storage` — should be a symlink pointing to `storage/app/public`

---

## Commit

```bash
git add .
git commit -m "feat: database migrations, models, and seeders"
git push
```

---

## Phase 2 complete ✓

Proceed to `phase-3-api.md`
