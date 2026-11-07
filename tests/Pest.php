<?php

use App\Actions\Shop\SummarizeCart;
use App\Models\Address;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Put a variant in the customer's cart (bypassing the HTTP layer).
 */
function addToCart(User $customer, ProductVariant $variant, int $quantity): void
{
    $customer->cart()->firstOrCreate()->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'price_at_add' => $variant->price,
    ]);
}

/**
 * Valid checkout form data for the customer's current cart.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutPayload(User $customer, array $overrides = []): array
{
    $summarizeCart = app(SummarizeCart::class);
    $summary = $summarizeCart->handle($customer->cart()->first());
    $totals = $summarizeCart->totals(
        $customer,
        $summary['subtotal'],
        $summary['weight_gram'],
        $overrides['shipping_service'] ?? 'reguler',
        $overrides['voucher_code'] ?? null,
    );

    return [
        'shipping_service' => 'reguler',
        'payment_method' => 'bank_transfer',
        'voucher_code' => null,
        'customer_note' => null,
        'terms' => '1',
        'idempotency_key' => (string) Str::uuid(),
        'expected_total' => $totals['grand_total'],
        ...$overrides,
    ];
}

/**
 * Place an order for one variant through the real checkout endpoint.
 *
 * @param  array<string, mixed>  $overrides
 */
function placeOrder(User $customer, ProductVariant $variant, int $quantity, array $overrides = []): Order
{
    if ($customer->address()->doesntExist()) {
        Address::factory()->for($customer)->create();
    }

    addToCart($customer, $variant, $quantity);

    test()->actingAs($customer)
        ->post(route('checkout.store'), checkoutPayload($customer, $overrides))
        ->assertSessionHasNoErrors();

    return $customer->orders()->latest('id')->firstOrFail();
}
