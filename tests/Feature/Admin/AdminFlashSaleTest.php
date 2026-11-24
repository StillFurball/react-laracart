<?php

use App\Models\Address;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('customers cannot manage flash sales', function () {
    $flashSale = FlashSale::factory()->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.flash-sales.index'))->assertForbidden();
    $this->actingAs($customer)
        ->post(route('admin.flash-sales.items.store', $flashSale), ['product_variant_id' => ProductVariant::factory()->create()->id, 'sale_price' => 1])
        ->assertForbidden();

    expect($flashSale->items()->count())->toBe(0);
});

test('an admin creates a flash sale and is sent to add its items', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.store'), [
            'name' => 'Payday Sale',
            'starts_at' => '2026-12-25 10:00',
            'ends_at' => '2026-12-25 22:00',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.flash-sales.edit', FlashSale::sole()));

    expect(FlashSale::sole())
        ->name->toBe('Payday Sale')
        ->is_active->toBeTrue();
});

test('a flash sale must end after it starts', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.store'), ['name' => 'Salah', 'starts_at' => '2026-12-25 10:00', 'ends_at' => '2026-12-25 09:00'])
        ->assertSessionHasErrors('ends_at');

    expect(FlashSale::count())->toBe(0);
});

test('an admin adds a variant with a sale price, quota and per-customer limit', function () {
    $flashSale = FlashSale::factory()->create();
    $variant = ProductVariant::factory()->create(['price' => 100000]);

    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.items.store', $flashSale), [
            'product_variant_id' => $variant->id,
            'sale_price' => 79000,
            'quota' => 20,
            'per_user_limit' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($flashSale->items()->sole())
        ->product_variant_id->toBe($variant->id)
        ->sale_price->toBe(79000)
        ->quota->toBe(20)
        ->per_user_limit->toBe(2)
        ->sold_count->toBe(0);
});

test('the sale price must be below the normal price', function () {
    $flashSale = FlashSale::factory()->create();
    $variant = ProductVariant::factory()->create(['price' => 100000]);

    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.items.store', $flashSale), ['product_variant_id' => $variant->id, 'sale_price' => 100000])
        ->assertSessionHasErrors(['sale_price' => 'Harga flash sale harus di bawah harga normal (Rp100.000).']);

    expect($flashSale->items()->count())->toBe(0);
});

test('a variant cannot be offered by two flash sales whose schedules overlap', function () {
    $offer = FlashSaleItem::factory()->create();
    $overlapping = FlashSale::factory()->create([
        'name' => 'Bentrok',
        'starts_at' => $offer->flashSale->starts_at->addHour(),
        'ends_at' => $offer->flashSale->ends_at->addDay(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.items.store', $overlapping), ['product_variant_id' => $offer->product_variant_id, 'sale_price' => 70000])
        ->assertSessionHasErrors('product_variant_id');

    $later = FlashSale::factory()->create([
        'starts_at' => $offer->flashSale->ends_at,
        'ends_at' => $offer->flashSale->ends_at->addDay(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.flash-sales.items.store', $later), ['product_variant_id' => $offer->product_variant_id, 'sale_price' => 70000])
        ->assertSessionHasNoErrors();
});

test('rescheduling a flash sale onto another sale of the same variant is refused', function () {
    $offer = FlashSaleItem::factory()->create();
    $later = FlashSaleItem::factory()->for(FlashSale::factory()->state([
        'starts_at' => $offer->flashSale->ends_at->addDay(),
        'ends_at' => $offer->flashSale->ends_at->addDays(2),
    ]))->create([
        'product_variant_id' => $offer->product_variant_id,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.flash-sales.update', $later->flashSale), [
            'name' => $later->flashSale->name,
            'starts_at' => $offer->flashSale->starts_at->format('Y-m-d H:i'),
            'ends_at' => $later->flashSale->ends_at->format('Y-m-d H:i'),
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('starts_at');
});

test('a quota cannot be lowered below the units already sold', function () {
    $offer = FlashSaleItem::factory()->quota(10, sold: 6)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.flash-sale-items.update', $offer), ['sale_price' => 80000, 'quota' => 5])
        ->assertSessionHasErrors(['quota' => 'Kuota tidak boleh di bawah jumlah yang sudah terjual (6).']);

    $this->actingAs($this->admin)
        ->put(route('admin.flash-sale-items.update', $offer), ['sale_price' => 75000, 'quota' => 6])
        ->assertSessionHasNoErrors();

    expect($offer->fresh())
        ->quota->toBe(6)
        ->sale_price->toBe(75000)
        ->sold_count->toBe(6);
});

test('an offer or a flash sale that has orders cannot be deleted', function () {
    $offer = FlashSaleItem::factory()->create();
    $customer = User::factory()->create();
    Address::factory()->for($customer)->create();
    placeOrder($customer, $offer->variant, 1);

    $this->actingAs($this->admin)
        ->delete(route('admin.flash-sale-items.destroy', $offer))
        ->assertSessionHasErrors('item');

    $this->actingAs($this->admin)
        ->delete(route('admin.flash-sales.destroy', $offer->flashSale))
        ->assertSessionHasErrors('flash_sale');

    expect($offer->fresh())->not->toBeNull();
});

test('the flash sale list shows status, units sold and revenue from paid orders only', function () {
    $offer = FlashSaleItem::factory()->create();
    $customer = User::factory()->create();
    Address::factory()->for($customer)->create();
    $paid = placeOrder($customer, $offer->variant, 2);
    $this->actingAs($customer)->post(route('orders.payment.store', $paid), ['outcome' => 'success']);
    placeOrder($customer, $offer->variant, 1);

    $this->actingAs($this->admin)
        ->get(route('admin.flash-sales.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/flash-sales/index')
            ->where('flashSales.data.0.status', 'running')
            ->where('flashSales.data.0.status_label', 'Berjalan')
            ->where('flashSales.data.0.sold_total', 3)
            ->where('flashSales.data.0.revenue', 160000));
});

test('the edit page searches variants that are not in the sale yet', function () {
    $offer = FlashSaleItem::factory()->create();
    $match = ProductVariant::factory()->create(['sku' => 'CARI-123']);
    ProductVariant::factory()->create(['sku' => 'LAIN-999']);

    $this->actingAs($this->admin)
        ->get(route('admin.flash-sales.edit', ['flash_sale' => $offer->flashSale, 'variant_q' => 'CARI']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/flash-sales/form')
            ->has('items', 1)
            ->has('variantOptions', 1)
            ->where('variantOptions.0.id', $match->id));
});
