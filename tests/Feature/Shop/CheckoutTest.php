<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
    Address::factory()->for($this->customer)->create(['city' => 'Bandung']);
});

test('checkout with enough stock creates a pending order and reserves stock', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5, 'price' => 50000, 'weight_gram' => 500]);
    addToCart($this->customer, $variant, 2);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer))
        ->assertRedirect();

    $order = Order::sole();
    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->subtotal)->toBe(100000)
        ->and($order->shipping_cost)->toBe(10000)
        ->and($order->grand_total)->toBe(110000)
        ->and($order->shipping_address['city'])->toBe('Bandung')
        ->and($order->items->sole()->unit_price)->toBe(50000)
        ->and($variant->fresh()->stock)->toBe(3)
        ->and($variant->stockMovements()->sole())
        ->type->toBe(StockMovementType::Reserve)
        ->quantity->toBe(-2)
        ->and($this->customer->cart->items)->toBeEmpty();
});

test('checkout is rejected when stock is insufficient', function () {
    $variant = ProductVariant::factory()->create(['stock' => 3]);
    addToCart($this->customer, $variant, 3);
    $payload = checkoutPayload($this->customer);
    ProductVariant::whereKey($variant->id)->update(['stock' => 1]);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), $payload)
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and($variant->fresh()->stock)->toBe(1);
});

test('submitting the same checkout twice creates only one order', function () {
    $variant = ProductVariant::factory()->create(['stock' => 5]);
    addToCart($this->customer, $variant, 2);
    $payload = checkoutPayload($this->customer);

    $this->actingAs($this->customer)->post(route('checkout.store'), $payload);
    addToCart($this->customer, $variant, 2);
    $this->actingAs($this->customer)->post(route('checkout.store'), $payload)
        ->assertRedirect(route('orders.show', Order::sole()));

    expect(Order::count())->toBe(1)
        ->and($variant->fresh()->stock)->toBe(3);
});

test('checkout is rejected when the total changed since the page was shown', function () {
    $variant = ProductVariant::factory()->create(['price' => 50000]);
    addToCart($this->customer, $variant, 1);
    $payload = checkoutPayload($this->customer);
    $variant->update(['price' => 60000]);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), $payload)
        ->assertSessionHasErrors('expected_total');

    expect(Order::count())->toBe(0);
});

test('the checkout page uses the latest price and announces the change', function () {
    $variant = ProductVariant::factory()->create(['price' => 50000]);
    addToCart($this->customer, $variant, 1);
    $variant->update(['price' => 45000]);

    $this->actingAs($this->customer)
        ->get(route('checkout.show'))
        ->assertInertia(fn ($page) => $page
            ->component('shop/checkout')
            ->where('totals.subtotal', 45000)
            ->has('priceChanges', 1));
});

test('checkout requires accepting the terms', function () {
    addToCart($this->customer, ProductVariant::factory()->create(), 1);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer, ['terms' => null]))
        ->assertSessionHasErrors('terms');

    expect(Order::count())->toBe(0);
});

test('checkout without an address redirects to the address form', function () {
    $customer = User::factory()->create();
    addToCart($customer, ProductVariant::factory()->create(), 1);

    $this->actingAs($customer)
        ->get(route('checkout.show'))
        ->assertRedirect(route('address.edit'));
});

test('a draft product cannot be checked out', function () {
    $variant = ProductVariant::factory()->create();
    addToCart($this->customer, $variant, 1);
    $variant->product->update(['status' => 'draft']);

    $this->actingAs($this->customer)
        ->post(route('checkout.store'), checkoutPayload($this->customer))
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
});

test('shipping cost rounds weight up to whole kilograms', function () {
    $variant = ProductVariant::factory()->create(['weight_gram' => 700]);
    $order = placeOrder($this->customer, $variant, 2, ['shipping_service' => 'kilat']);

    expect($order->shipping_cost)->toBe(2 * 18000)
        ->and($order->courier_service)->toBe('Kilat');
});
