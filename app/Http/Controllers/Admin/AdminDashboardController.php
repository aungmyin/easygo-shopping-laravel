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
