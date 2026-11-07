<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::patch('orders/{order}/note', [OrderController::class, 'updateNote'])->name('orders.note.update');

    Route::resource('categories', CategoryController::class)->except(['create', 'show']);

    Route::resource('products', ProductController::class)->except('show');
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');
    Route::post('variants/{variant}/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('variants.stock-adjustments.store');
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('product-images/{productImage}', [ProductImageController::class, 'destroy'])->name('product-images.destroy');

    Route::get('stock', [StockController::class, 'index'])->name('stock.index');

    Route::resource('vouchers', VoucherController::class)->except('show');
});
