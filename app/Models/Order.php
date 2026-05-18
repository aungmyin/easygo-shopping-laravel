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
