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
