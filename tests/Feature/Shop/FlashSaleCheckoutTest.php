<?php

use App\Models\Address;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;

beforeEach(function () {
    $this->customer = User::factory()->create();
    Address::factory()->for($this->customer)->create();
});

test('a running flash sale price is charged, snapshotted on the order and counted against the quota', function () {
    $offer = FlashSaleItem::factory()->quota(10)->create();

    $order = placeOrder($this->customer, $offer->variant, 2);

    $item = $order->items()->sole();
    expect($order->subtotal)->toBe(160000);
    expect($item)
        ->unit_price->toBe(80000)
        ->regular_unit_price->toBe(100000)
        ->line_total->toBe(160000)
        ->flash_sale_item_id->toBe($offer->id);
    expect($offer->fresh()->sold_count)->toBe(2);
});

test('the normal price applies outside a running sale', function (FlashSale $flashSale) {
    $offer = FlashSaleItem::factory()->for($flashSale)->create();

    $order = placeOrder($this->customer, $offer->variant, 1);

    expect($order->items()->sole())
        ->unit_price->toBe(100000)
        ->regular_unit_price->toBeNull()
        ->flash_sale_item_id->toBeNull();
    expect($offer->fresh()->sold_count)->toBe(0);
})->with([
    'not started yet' => fn () => FlashSale::factory()->upcoming()->create(),
    'already ended' => fn () => FlashSale::factory()->ended()->create(),
    'switched off' => fn () => FlashSale::factory()->inactive()->create(),
]);

test('a sale price that is no longer below the normal price is ignored', function () {
    $offer = FlashSaleItem::factory()->create();
    $offer->variant->update(['price' => 75000]);

    $order = placeOrder($this->customer, $offer->variant, 1);

    expect($order->items()->sole()->unit_price)->toBe(75000);
});

test('a quantity above the remaining quota pays the normal price and the cart says why', function () {
    $offer = FlashSaleItem::factory()->quota(5, sold: 4)->create();
    addToCart($this->customer, $offer->variant, 2);

    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('items.0.price', 100000)
            ->where('items.0.flash_sale', null)
            ->where('items.0.sale_notice', 'Harga flash sale tinggal untuk 1 barang. Kurangi jumlahnya untuk mendapatkan harga itu.'));
});

test('the per-customer limit counts units from earlier orders but not from cancelled ones', function () {
    $offer = FlashSaleItem::factory()->perUserLimit(2)->create();
    $first = placeOrder($this->customer, $offer->variant, 2);

    addToCart($this->customer, $offer->variant, 1);
    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('items.0.price', 100000)
            ->where('items.0.sale_notice', 'Jatah flash sale kamu untuk barang ini sudah terpakai (maks. 2 per pelanggan); harga normal berlaku.'));

    $this->actingAs($this->customer)->post(route('orders.cancel', $first));

    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('items.0.price', 80000)
            ->where('items.0.sale_notice', null));
});

test('the per-customer limit does not restrict other customers', function () {
    $offer = FlashSaleItem::factory()->perUserLimit(1)->create();
    placeOrder(User::factory()->create(), $offer->variant, 1);

    $order = placeOrder($this->customer, $offer->variant, 1);

    expect($order->items()->sole()->unit_price)->toBe(80000);
});

test('checkout is refused when the quota ran out after the summary was shown', function () {
    $offer = FlashSaleItem::factory()->quota(3)->create();
    addToCart($this->customer, $offer->variant, 2);
    $payload = checkoutPayload($this->customer);

    // Another customer takes the remaining units meanwhile.
    FlashSaleItem::whereKey($offer->id)->update(['sold_count' => 3]);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), $payload)
        ->assertSessionHasErrors('expected_total');

    expect(Order::count())->toBe(0);
    expect($offer->fresh()->sold_count)->toBe(3);
});

test('cancelling or expiring an order returns its units to the quota', function (string $outcome) {
    $offer = FlashSaleItem::factory()->quota(5)->create();
    $order = placeOrder($this->customer, $offer->variant, 2);
    expect($offer->fresh()->sold_count)->toBe(2);

    match ($outcome) {
        'cancelled' => $this->actingAs($this->customer)->post(route('orders.cancel', $order)),
        'expired' => $this->actingAs($this->customer)->post(route('orders.payment.store', $order), ['outcome' => 'expired']),
    };

    expect($order->fresh()->status->value)->toBe($outcome);
    expect($offer->fresh()->sold_count)->toBe(0);
})->with(['cancelled', 'expired']);

test('adding to the cart during a sale remembers the sale price, so the cart announces no change', function () {
    $offer = FlashSaleItem::factory()->create();

    $this->actingAs($this->customer)
        ->post(route('cart.items.store'), ['product_variant_id' => $offer->product_variant_id, 'quantity' => 1])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('items.0.price', 80000)
            ->where('items.0.regular_price', 100000)
            ->where('items.0.flash_sale.discount_percent', 20)
            ->where('priceChanges', []));
});

test('the cart announces the normal price once the sale is over', function () {
    $offer = FlashSaleItem::factory()->create();
    $this->actingAs($this->customer)
        ->post(route('cart.items.store'), ['product_variant_id' => $offer->product_variant_id, 'quantity' => 1]);

    $this->travelTo($offer->flashSale->ends_at->addMinute());

    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertInertia(fn ($page) => $page
            ->where('items.0.price', 100000)
            ->where('priceChanges.0', "Harga {$offer->variant->product->name} berubah dari Rp80.000 menjadi Rp100.000."));
});

test('a voucher applies on top of the flash sale price', function () {
    $offer = FlashSaleItem::factory()->create();
    Voucher::factory()->percent(10)->create(['code' => 'EXTRA10']);

    $order = placeOrder($this->customer, $offer->variant, 1, ['voucher_code' => 'EXTRA10']);

    expect($order->subtotal)->toBe(80000);
    expect($order->discount_total)->toBe(8000);
});
