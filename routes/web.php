<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (Request $request) => $request->user()->isAdmin()
        ? to_route('admin.orders.index')
        : to_route('home'))->name('dashboard');
});

require __DIR__.'/shop.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
