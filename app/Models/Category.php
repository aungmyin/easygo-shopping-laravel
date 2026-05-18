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
