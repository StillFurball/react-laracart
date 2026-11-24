<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

test('the landing page groups active products into top-level departments with their lowest price', function () {
    $fashion = Category::factory()->create(['name' => 'Fashion']);
    $shirts = Category::factory()->create(['name' => 'Kaos Pria', 'parent_id' => $fashion->id]);
    $empty = Category::factory()->create(['name' => 'Kosong']);

    $jacket = Product::factory()->for($shirts)->create(['name' => 'Jaket Denim']);
    ProductVariant::factory()->for($jacket)->create(['price' => 250000]);
    $shirt = Product::factory()->for($fashion)->create(['name' => 'Kaos Polos']);
    ProductVariant::factory()->for($shirt)->create(['price' => 90000]);
    $draft = Product::factory()->for($shirts)->create(['status' => ProductStatus::Draft]);
    ProductVariant::factory()->for($draft)->create(['price' => 1000]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('landing')
            ->has('departments', 1)
            ->where('departments.0.slug', $fashion->slug)
            ->where('departments.0.min_price', 90000)
            ->where('departments.0.product_count', 2)
            ->has('menuCategories', 2)
            ->where('menuCategories.1.slug', $empty->slug));
});

test('everyday items are the lightest products with their weight and colours', function () {
    $heavy = Product::factory()->create(['name' => 'Jaket Denim', 'weight_gram' => 800]);
    ProductVariant::factory()->for($heavy)->create(['attributes' => ['Ukuran' => 'M']]);
    $light = Product::factory()->create(['name' => 'Kaos Polos', 'weight_gram' => 200]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'M', 'Warna' => 'Hitam']]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'L', 'Warna' => 'Hitam']]);
    ProductVariant::factory()->for($light)->create(['attributes' => ['Ukuran' => 'M', 'Warna' => 'Putih']]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('essentials', 2)
            ->where('essentials.0.name', 'Kaos Polos')
            ->where('essentials.0.weight_gram', 200)
            ->where('essentials.0.colors', ['Hitam', 'Putih'])
            ->where('essentials.1.name', 'Jaket Denim'));
});

test('best deals are products whose cheapest variant is discounted, biggest discount first', function () {
    $small = Product::factory()->create(['name' => 'Diskon Kecil']);
    ProductVariant::factory()->for($small)->create(['price' => 90000, 'compare_at_price' => 100000]);
    $big = Product::factory()->create(['name' => 'Diskon Besar']);
    ProductVariant::factory()->for($big)->create(['price' => 80000, 'compare_at_price' => 100000]);
    ProductVariant::factory()->for($big)->create(['price' => 120000, 'compare_at_price' => 200000]);
    $notLower = Product::factory()->create();
    ProductVariant::factory()->for($notLower)->create(['price' => 90000, 'compare_at_price' => 90000]);
    $onlyPricierVariant = Product::factory()->create();
    ProductVariant::factory()->for($onlyPricierVariant)->create(['price' => 50000, 'compare_at_price' => null]);
    ProductVariant::factory()->for($onlyPricierVariant)->create(['price' => 70000, 'compare_at_price' => 99000]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('deals', 2)
            ->where('deals.0.name', 'Diskon Besar')
            ->where('deals.0.compare_at_price', 100000)
            ->where('deals.1.name', 'Diskon Kecil')
            ->has('essentials', 2));
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
