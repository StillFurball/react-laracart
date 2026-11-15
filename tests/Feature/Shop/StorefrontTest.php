<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Voucher;
use Inertia\Testing\AssertableInertia as Assert;

test('the landing page lists active products lightest first with their weight, colours and department', function () {
    $fashion = Category::factory()->create(['name' => 'Fashion']);
    $shirts = Category::factory()->create(['name' => 'Kaos Pria', 'parent_id' => $fashion->id]);

    $heavy = Product::factory()->for($shirts)->create(['name' => 'Jaket Denim', 'weight_gram' => 800]);
    ProductVariant::factory()->for($heavy)->create(['attributes' => ['Ukuran' => 'M']]);
    $light = Product::factory()->for($shirts)->create(['name' => 'Kaos Polos', 'weight_gram' => 200]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'M', 'Warna' => 'Hitam']]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'L', 'Warna' => 'Hitam']]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'M', 'Warna' => 'Putih']]);
    $draft = Product::factory()->for($shirts)->create(['status' => ProductStatus::Draft]);
    ProductVariant::factory()->for($draft)->create();

    Voucher::factory()->create(['code' => 'AKTIF', 'is_active' => true, 'ends_at' => now()->addWeek()]);
    Voucher::factory()->create(['code' => 'LEWAT', 'is_active' => true, 'ends_at' => now()->subDay()]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('landing')
            ->has('products', 2)
            ->where('products.0.name', 'Kaos Polos')
            ->where('products.0.weight_gram', 200)
            ->where('products.0.colors', ['Hitam', 'Putih'])
            ->where('products.0.department', 'Fashion')
            ->where('products.1.name', 'Jaket Denim')
            ->has('categories', 1)
            ->where('categories.0.slug', $fashion->slug)
            ->where('shipping.services.0.rate_per_kg', config('shop.shipping.services.reguler.rate_per_kg'))
            ->where('vouchers', ['AKTIF']));
});

test('the catalogue lives at /products', function () {
    $product = Product::factory()->create(['weight_gram' => 350]);
    ProductVariant::factory()->for($product)->create();

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shop/index')
            ->where('products.data.0.weight_gram', 350));
});
