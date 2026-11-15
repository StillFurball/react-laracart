<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;

test('responses carry the security headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('a customer cannot pay through the simulator once it is turned off', function () {
    config(['shop.payment_simulator' => false]);
    $order = Order::factory()->create();

    $this->actingAs($order->user)
        ->post(route('orders.payment.store', $order), ['outcome' => 'success'])
        ->assertNotFound();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('with the simulator off an admin confirms a manual transfer', function () {
    config(['shop.payment_simulator' => false]);
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch(route('admin.orders.status.update', $order), ['status' => 'paid'])
        ->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->payments()->sole())
        ->gateway->toBe('manual')
        ->status->toBe(PaymentStatus::Paid)
        ->amount->toBe($order->grand_total);
});

test('qris is not accepted at checkout while the simulator is off', function () {
    config(['shop.payment_simulator' => false]);

    $this->actingAs(User::factory()->create())
        ->post(route('checkout.store'), ['payment_method' => 'qris'])
        ->assertSessionHasErrors('payment_method');
});

test('a queued order email keeps the status it was dispatched for', function () {
    $order = Order::factory()->create();
    $notification = new OrderStatusUpdated($order);

    $order->update(['status' => OrderStatus::Paid]);

    expect($notification->toMail($order->user)->subject)->toContain('berhasil dibuat');
});

test('shop:make-admin promotes a registered user and rejects unknown emails', function () {
    $user = User::factory()->create();

    $this->artisan('shop:make-admin', ['email' => $user->email])->assertSuccessful();
    $this->artisan('shop:make-admin', ['email' => 'nobody@example.com'])->assertFailed();

    expect($user->fresh()->isAdmin())->toBeTrue();
});
