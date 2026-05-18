<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Order, Product, User};

class AdminDashboardController extends Controller
{
    public function index()
    {
        return response()->json(['data' => [
            'total_orders'   => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_revenue'  => Order::where('payment_status', 'paid')->sum('total'),
            'total_products' => Product::count(),
            'low_stock'      => Product::where('stock', '<', 10)->count(),
            'total_users'    => User::where('role', 'customer')->count(),
            'new_users_30d'  => User::where('role', 'customer')
                                    ->where('created_at', '>=', now()->subDays(30))->count(),
            'recent_orders'  => Order::with('user')->latest()->take(10)->get(),
        ]]);
    }
}
