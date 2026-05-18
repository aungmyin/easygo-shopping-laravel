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

Route::redirect('/', '/admin/login');
Route::redirect('/login', '/admin/login')->name('login');

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
