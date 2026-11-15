<?php

use App\Actions\Shop\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStock;
use App\Notifications\NewPaidOrder;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\PaymentReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->variant = ProductVariant::factory()->create(['stock' => 12, 'low_stock_threshold' => 5]);
});

test('placing an order emails the customer', function () {
    $order = placeOrder($this->customer, $this->variant, 1);

    Notification::assertSentTo($this->customer, OrderStatusUpdated::class, fn (OrderStatusUpdated $notification) => $notification->order->is($order)
        && $notification->order->status === OrderStatus::PendingPayment);
    Notification::assertNotSentTo($this->admin, NewPaidOrder::class);
});

test('a rejected checkout sends no email', function () {
    addToCart($this->customer, $this->variant, 1);
    $this->variant->update(['stock' => 0]);

    $this->actingAs($this->customer)->post(route('checkout.store'), checkoutPayload($this->customer));

    Notification::assertNothingSent();
});

test('a successful payment emails the customer and the admins', function () {
    $order = placeOrder($this->customer, $this->variant, 1);

    $this->post(route('orders.payment.store', $order), ['outcome' => 'success']);

    Notification::assertSentTo($this->customer, OrderStatusUpdated::class, fn (OrderStatusUpdated $notification) => $notification->order->status === OrderStatus::Paid);
    Notification::assertSentTo($this->admin, NewPaidOrder::class);
    Notification::assertNotSentTo($this->customer, NewPaidOrder::class);
});

test('shipping an order emails the customer', function () {
    $order = Order::factory()->for($this->customer)->status(OrderStatus::Processing)->create();

    app(ChangeOrderStatus::class)->handle($order, OrderStatus::Shipped, $this->admin, null, 'RESI123');

    Notification::assertSentTo($this->customer, OrderStatusUpdated::class, fn (OrderStatusUpdated $notification) => $notification->order->status === OrderStatus::Shipped);
});

test('admins are emailed once when stock drops to the low-stock threshold', function () {
    placeOrder($this->customer, $this->variant, 6);

    Notification::assertNotSentTo($this->admin, LowStock::class);

    placeOrder($this->customer, $this->variant, 1);
    placeOrder($this->customer, $this->variant, 1);

    Notification::assertSentToTimes($this->admin, LowStock::class, 1);
});

test('the payment reminder is sent once for orders close to their deadline', function () {
    $dueSoon = Order::factory()->for($this->customer)->create(['payment_due_at' => now()->addHour()]);
    $dueLater = Order::factory()->for($this->customer)->create(['payment_due_at' => now()->addHours(10)]);
    $paid = Order::factory()->for($this->customer)->status(OrderStatus::Paid)->create(['payment_due_at' => now()->addHour()]);

    $this->artisan('orders:remind-payment')->assertSuccessful();
    $this->artisan('orders:remind-payment')->assertSuccessful();

    Notification::assertSentToTimes($this->customer, PaymentReminder::class, 1);
    Notification::assertSentTo($this->customer, PaymentReminder::class, fn (PaymentReminder $notification) => $notification->order->is($dueSoon));
    expect($dueLater->fresh()->payment_reminder_sent_at)->toBeNull()
        ->and($paid->fresh()->payment_reminder_sent_at)->toBeNull();
});

test('delivered orders are completed automatically after the configured days', function () {
    $overdue = Order::factory()->status(OrderStatus::Delivered)->create();
    $overdue->shipment()->create(['courier' => 'Kurir Demo', 'service' => 'Reguler', 'tracking_number' => 'A1', 'shipped_at' => now()->subDays(5), 'delivered_at' => now()->subDays(4)]);
    $recent = Order::factory()->status(OrderStatus::Delivered)->create();
    $recent->shipment()->create(['courier' => 'Kurir Demo', 'service' => 'Reguler', 'tracking_number' => 'A2', 'shipped_at' => now()->subDays(2), 'delivered_at' => now()->subDay()]);

    $this->artisan('orders:auto-complete')->assertSuccessful();

    expect($overdue->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($overdue->statusHistories()->sole()->user_id)->toBeNull()
        ->and($recent->fresh()->status)->toBe(OrderStatus::Delivered);
});
