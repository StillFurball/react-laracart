<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
            ->has('menuCategories', 1)
            ->where('menuCategories.0.slug', $fashion->slug));
});

test('the landing page shows the original price of the cheapest variant only when it is a discount', function () {
    $discounted = Product::factory()->create(['weight_gram' => 100]);
    ProductVariant::factory()->for($discounted)->create(['price' => 80000, 'compare_at_price' => 100000]);
    ProductVariant::factory()->for($discounted)->create(['price' => 120000, 'compare_at_price' => 200000]);
    $notLower = Product::factory()->create(['weight_gram' => 200]);
    ProductVariant::factory()->for($notLower)->create(['price' => 90000, 'compare_at_price' => 90000]);
    $mixed = Product::factory()->create(['weight_gram' => 300]);
    ProductVariant::factory()->for($mixed)->create(['price' => 50000, 'compare_at_price' => null]);
    ProductVariant::factory()->for($mixed)->create(['price' => 70000, 'compare_at_price' => 99000]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.0.compare_at_price', 100000)
            ->where('products.1.compare_at_price', null)
            ->where('products.2.compare_at_price', null));
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
