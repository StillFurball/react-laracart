<?php

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;

beforeEach(function () {
    $this->customer = User::factory()->create();
    Address::factory()->for($this->customer)->create();
});

test('a voucher is rejected below the minimum purchase and shows the shortfall', function () {
    Voucher::factory()->create(['code' => 'MIN200', 'min_purchase' => 200000]);
    addToCart($this->customer, ProductVariant::factory()->create(['price' => 150000]), 1);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer, ['voucher_code' => 'MIN200']))
        ->assertSessionHasErrors(['voucher_code' => 'Kurang Rp50.000 untuk memakai voucher ini (minimum belanja Rp200.000).']);

    expect(Order::count())->toBe(0);
});

test('a percentage discount is capped at the maximum discount', function () {
    Voucher::factory()->percent(10, maxDiscount: 20000)->create(['code' => 'HEMAT10']);

    $order = placeOrder($this->customer, ProductVariant::factory()->create(['price' => 300000, 'weight_gram' => 500]), 1, ['voucher_code' => 'hemat10']);

    expect($order->discount_total)->toBe(20000)
        ->and($order->voucher_code)->toBe('HEMAT10')
        ->and($order->grand_total)->toBe(300000 - 20000 + 10000);
});

test('a percentage discount rounds down and never discounts shipping', function () {
    Voucher::factory()->percent(15)->create(['code' => 'P15']);

    $order = placeOrder($this->customer, ProductVariant::factory()->create(['price' => 33333]), 1, ['voucher_code' => 'P15']);

    expect($order->discount_total)->toBe(4999)
        ->and($order->grand_total)->toBe(33333 - 4999 + $order->shipping_cost);
});

test('a voucher whose quota is used by a pending order is rejected', function () {
    Voucher::factory()->create(['code' => 'SATU', 'quota' => 1]);
    placeOrder(User::factory()->create(), ProductVariant::factory()->create(), 1, ['voucher_code' => 'SATU']);
    addToCart($this->customer, ProductVariant::factory()->create(), 1);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer, ['voucher_code' => 'SATU']))
        ->assertSessionHasErrors(['voucher_code' => 'Kuota voucher sudah habis.']);
});

test('voucher quota is available again after the order using it expires', function () {
    Voucher::factory()->create(['code' => 'SATU', 'quota' => 1]);
    $first = placeOrder(User::factory()->create(), ProductVariant::factory()->create(), 1, ['voucher_code' => 'SATU']);
    $first->update(['status' => OrderStatus::Expired]);

    $order = placeOrder($this->customer, ProductVariant::factory()->create(), 1, ['voucher_code' => 'SATU']);

    expect($order->voucher_code)->toBe('SATU');
});

test('the per-customer limit applies only to that customer', function () {
    Voucher::factory()->create(['code' => 'SEKALI', 'per_user_limit' => 1]);
    placeOrder($this->customer, ProductVariant::factory()->create(), 1, ['voucher_code' => 'SEKALI']);
    addToCart($this->customer, ProductVariant::factory()->create(), 1);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer, ['voucher_code' => 'SEKALI']))
        ->assertSessionHasErrors('voucher_code');

    $other = placeOrder(User::factory()->create(), ProductVariant::factory()->create(), 1, ['voucher_code' => 'SEKALI']);
    expect($other->voucher_code)->toBe('SEKALI');
});

test('an expired or inactive voucher is rejected', function (array $attributes) {
    Voucher::factory()->create(['code' => 'X1', ...$attributes]);
    addToCart($this->customer, ProductVariant::factory()->create(), 1);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer, ['voucher_code' => 'X1']))
        ->assertSessionHasErrors(['voucher_code' => 'Voucher tidak aktif atau sudah tidak berlaku.']);
})->with([
    'past end date' => [['ends_at' => now()->subDay()]],
    'not started' => [['starts_at' => now()->addDay()]],
    'inactive' => [['is_active' => false]],
]);
