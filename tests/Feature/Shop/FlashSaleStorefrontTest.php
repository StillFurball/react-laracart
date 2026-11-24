<?php

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Testing\AssertableInertia as Assert;

test('the product page shows the running offer of each variant with its quota', function () {
    $offer = FlashSaleItem::factory()->quota(20, sold: 5)->perUserLimit(2)->create();
    $other = ProductVariant::factory()->for($offer->variant->product)->create();

    $this->get(route('products.show', $offer->variant->product))
        ->assertInertia(fn (Assert $page) => $page
            ->component('shop/product')
            ->where('product.variants.0.flash_sale.price', 80000)
            ->where('product.variants.0.flash_sale.discount_percent', 20)
            ->where('product.variants.0.flash_sale.remaining', 15)
            ->where('product.variants.0.flash_sale.per_user_limit', 2)
            ->where('product.variants.0.flash_sale.ends_at', $offer->flashSale->ends_at->toIso8601String())
            ->where('product.variants.1.id', $other->id)
            ->where('product.variants.1.flash_sale', null));
});

test('the product page announces an upcoming flash sale', function () {
    $offer = FlashSaleItem::factory()->for(FlashSale::factory()->upcoming()->state(['name' => 'Payday']))->create();

    $this->get(route('products.show', $offer->variant->product))
        ->assertInertia(fn (Assert $page) => $page
            ->where('product.variants.0.flash_sale', null)
            ->where('product.upcoming_flash_sale.name', 'Payday')
            ->where('product.upcoming_flash_sale.price', 80000));
});

test('catalogue cards carry the cheapest running offer', function () {
    $offer = FlashSaleItem::factory()->create(['sale_price' => 60000]);
    FlashSaleItem::factory()->for($offer->flashSale)->create([
        'product_variant_id' => ProductVariant::factory()->for($offer->variant->product)->state(['price' => 90000]),
        'sale_price' => 85000,
    ]);

    $this->get(route('products.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.flash_sale.price', 60000)
            ->where('products.data.0.flash_sale.regular_price', 100000)
            ->where('products.data.0.flash_sale.discount_percent', 40));
});

test('the flash sale page lists running sales and upcoming ones, hiding unavailable products', function () {
    $running = FlashSaleItem::factory()->create();
    $draft = FlashSaleItem::factory()->for($running->flashSale)->create();
    $draft->variant->product->update(['status' => 'draft']);
    $upcoming = FlashSaleItem::factory()->for(FlashSale::factory()->upcoming())->create();
    FlashSaleItem::factory()->for(FlashSale::factory()->ended())->create();

    $this->get(route('flash-sale.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shop/flash-sale')
            ->has('running', 1)
            ->has('running.0.items', 1)
            ->where('running.0.items.0.product_slug', $running->variant->product->slug)
            ->has('upcoming', 1)
            ->where('upcoming.0.items.0.id', $upcoming->id));
});

test('the landing page features the running flash sale, else the next one', function () {
    $upcoming = FlashSaleItem::factory()->for(FlashSale::factory()->upcoming()->state(['name' => 'Nanti']))->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('flashSale.name', 'Nanti')
            ->where('flashSale.status', 'scheduled'));

    $running = FlashSaleItem::factory()->for(FlashSale::factory()->state(['name' => 'Sekarang']))->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('flashSale.name', 'Sekarang')
            ->where('flashSale.status', 'running')
            ->where('flashSale.items.0.id', $running->id));
});

test('a sale with no sellable products is not featured', function () {
    $offer = FlashSaleItem::factory()->create();
    Product::whereKey($offer->variant->product_id)->update(['status' => 'archived']);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('flashSale', null));
});
