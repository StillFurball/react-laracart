<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('customers cannot access the admin panel', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertForbidden();
})->with(['admin.orders.index', 'admin.products.index', 'admin.categories.index', 'admin.vouchers.index', 'admin.stock.index']);

test('customers cannot change an order status', function () {
    $order = Order::factory()->status(OrderStatus::Paid)->create();

    $this->actingAs($order->user)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'processing'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('an invalid status transition is rejected', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'shipped', 'tracking_number' => 'RESI1'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('an admin cannot mark an order paid manually', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'paid'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('shipping an order records the tracking number and history', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'shipped', 'tracking_number' => 'DEMO123'])
        ->assertRedirect(route('admin.orders.show', $order));

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped)
        ->and($order->shipment->tracking_number)->toBe('DEMO123')
        ->and($order->statusHistories()->sole())
        ->to_status->toBe(OrderStatus::Shipped)
        ->user_id->toBe($this->admin->id);
});

test('shipping an order requires a tracking number', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'shipped'])
        ->assertSessionHasErrors('tracking_number');

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('the tracking number is visible on the customer order page', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->create();
    $this->actingAs($this->admin)
        ->patch(route('admin.orders.status.update', $order), ['status' => 'shipped', 'tracking_number' => 'DEMO123']);

    $this->actingAs($order->user)
        ->get(route('orders.show', $order))
        ->assertInertia(fn ($page) => $page
            ->component('account/orders/show')
            ->where('order.shipment.tracking_number', 'DEMO123'));
});

test('the internal note is not shown to the customer', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.orders.note.update', $order), ['internal_note' => 'Cek ulang alamat'])
        ->assertRedirect();

    expect($order->fresh()->internal_note)->toBe('Cek ulang alamat');

    $this->actingAs($order->user)
        ->get(route('orders.show', $order))
        ->assertInertia(fn ($page) => $page->missing('order.internal_note'));
});
