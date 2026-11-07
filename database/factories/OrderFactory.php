<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Creates an order without items; use the checkout flow in tests that need stock effects.
 *
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'user_id' => User::factory(),
            'status' => OrderStatus::PendingPayment,
            'subtotal' => 140000,
            'discount_total' => 0,
            'shipping_cost' => 10000,
            'tax_total' => 0,
            'grand_total' => 150000,
            'shipping_address' => [
                'recipient_name' => fake()->name(),
                'phone' => '081200000000',
                'address_line' => fake()->streetAddress(),
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40111',
            ],
            'courier' => 'Kurir Demo',
            'courier_service' => 'Reguler',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_due_at' => now()->addDay(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }

    /**
     * Set the order status.
     */
    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
