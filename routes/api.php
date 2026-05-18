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

Route::prefix('v1')->middleware('throttle:api')->group(function () {

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
