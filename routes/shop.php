<?php

use App\Http\Controllers\Shop\AddressController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CartItemController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\OrderPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');

Route::middleware('auth')->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('account/address', [AddressController::class, 'edit'])->name('address.edit');
    Route::put('account/address', [AddressController::class, 'update'])->name('address.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/payment', [OrderPaymentController::class, 'store'])->name('orders.payment.store');
});
