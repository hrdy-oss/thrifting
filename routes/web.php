<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\StorefrontController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/upcoming', [StorefrontController::class, 'upcoming'])->name('upcoming.index');
Route::get('/products', [StorefrontController::class, 'catalog'])->name('products.index');
Route::get('/products/{product}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'store'])->name('cart.store');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/{product}', [CheckoutController::class, 'direct'])->name('checkout.direct');
Route::get('/orders/track/{token}', [OrderTrackingController::class, 'show'])->name('orders.track');
Route::get('/login/admin', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/login/admin', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/products/upcoming', [AdminProductController::class, 'upcoming'])->name('products.upcoming');
    Route::get('/products/archived', [AdminProductController::class, 'archived'])->name('products.archived');
    Route::patch('/products/{product}/upcoming', [AdminProductController::class, 'toggleUpcoming'])->name('products.toggle-upcoming');
    Route::patch('/products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', AdminProductController::class)->except(['show']);
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
});
