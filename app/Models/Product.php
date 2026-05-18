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
