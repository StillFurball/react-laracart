<?php

use App\Enums\OrderStatus;
use App\Models\ProductVariant;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->variant = ProductVariant::factory()->create(['stock' => 5]);
    $this->order = placeOrder($this->customer, $this->variant, 2);
});

test('a customer can cancel an unpaid order and the stock is returned', function () {
    expect($this->variant->fresh()->stock)->toBe(3);

    $this->actingAs($this->customer)
        ->post(route('orders.cancel', $this->order))
        ->assertRedirect(route('orders.show', $this->order));

    expect($this->order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->variant->fresh()->stock)->toBe(5);
});

test('a paid order cannot be cancelled by the customer', function () {
    $this->actingAs($this->customer)->post(route('orders.payment.store', $this->order), ['outcome' => 'success']);

    $this->actingAs($this->customer)
        ->post(route('orders.cancel', $this->order))
        ->assertSessionHasErrors('status');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->variant->fresh()->stock)->toBe(3);
});

test('a customer cannot cancel someone else\'s order', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('orders.cancel', $this->order))
        ->assertForbidden();

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('a customer can confirm a delivered order as completed', function () {
    $this->order->update(['status' => OrderStatus::Delivered]);

    $this->actingAs($this->customer)->post(route('orders.complete', $this->order));

    expect($this->order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('an order that is not delivered cannot be completed by the customer', function () {
    $this->actingAs($this->customer)
        ->post(route('orders.complete', $this->order))
        ->assertSessionHasErrors('status');

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});
