<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->variant = ProductVariant::factory()->create(['stock' => 5, 'price' => 70000, 'weight_gram' => 500]);
    $this->order = placeOrder($this->customer, $this->variant, 2);
});

function simulate(Order $order, string $outcome, ?User $as = null)
{
    return test()->actingAs($as ?? $order->user)
        ->post(route('orders.payment.store', $order), ['outcome' => $outcome]);
}

test('a successful simulated payment marks the order paid with the order total', function () {
    expect($this->order->grand_total)->toBe(150000);

    simulate($this->order, 'success')->assertRedirect(route('orders.show', $this->order));

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->order->payments()->sole())
        ->status->toBe(PaymentStatus::Paid)
        ->amount->toBe(150000);
});

test('a failed simulated payment keeps the order pending so the customer can retry', function () {
    simulate($this->order, 'failed');

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($this->order->payments()->sole()->status)->toBe(PaymentStatus::Failed);

    simulate($this->order, 'success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('processing a success again does not change status or record another payment', function () {
    simulate($this->order, 'success');
    simulate($this->order, 'success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->order->payments()->count())->toBe(1);
});

test('the expired outcome expires the order and releases stock', function () {
    simulate($this->order, 'expired');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($this->variant->fresh()->stock)->toBe(5)
        ->and($this->variant->stockMovements()->where('type', StockMovementType::Release)->sole()->quantity)->toBe(2);
});

test('paying after the deadline expires the order instead', function () {
    $this->travelTo(now()->addHours(25));

    simulate($this->order, 'success');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($this->order->payments()->count())->toBe(0);
});

test('another customer cannot pay for the order', function () {
    simulate($this->order, 'success', User::factory()->create())->assertForbidden();

    expect($this->order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('another customer cannot view the order', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('orders.show', $this->order))
        ->assertForbidden();
});

test('the expire command expires overdue orders and releases their stock', function () {
    $notYetDue = placeOrder($this->customer, $this->variant, 1);
    Order::whereKey($this->order->id)->update(['payment_due_at' => now()->subMinute()]);

    $this->artisan('orders:expire')->assertSuccessful();

    expect($this->order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($notYetDue->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($this->variant->fresh()->stock)->toBe(4)
        ->and($this->variant->stockMovements()->where('type', StockMovementType::Release)->count())->toBe(1);
});
