<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('the sales report counts only paid orders within the selected period', function () {
    $paid = Order::factory()->status(OrderStatus::Completed)->create([
        'subtotal' => 200000, 'discount_total' => 20000, 'shipping_cost' => 10000, 'grand_total' => 190000,
    ]);
    $paid->items()->create([
        'product_variant_id' => ProductVariant::factory()->create()->id,
        'product_name' => 'Kaos Polos', 'variant_name' => 'M', 'sku' => 'KP-M', 'unit_price' => 100000,
        'quantity' => 2, 'weight_gram' => 200, 'line_total' => 200000,
    ]);
    Order::factory()->create(['grand_total' => 999000]);
    Order::factory()->status(OrderStatus::Cancelled)->create(['grand_total' => 999000]);
    Order::factory()->status(OrderStatus::Paid)->create(['created_at' => now()->subMonths(2), 'grand_total' => 999000]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard', ['from' => now()->subDays(7)->toDateString(), 'to' => now()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('sales.today', 190000)
            ->where('report.orders', 1)
            ->where('report.gross', 200000)
            ->where('report.discount', 20000)
            ->where('report.shipping', 10000)
            ->where('report.net', 180000)
            ->where('report.daily.0.net', 180000)
            ->where('topProducts.0', ['name' => 'Kaos Polos', 'quantity' => 2, 'revenue' => 200000])
            ->where('statusCounts', fn ($counts) => collect($counts)->firstWhere('value', 'pending_payment')['total'] === 1));
});

test('the report end date cannot be before the start date', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard', ['from' => '2026-11-08', 'to' => '2026-11-01']))
        ->assertSessionHasErrors('to');
});
