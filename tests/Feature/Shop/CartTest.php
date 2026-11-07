<?php

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;

test('guests are sent to login when adding to the cart', function () {
    $variant = ProductVariant::factory()->create();

    $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
        ->assertRedirect(route('login'));
});

test('adding the same variant twice merges the quantity', function () {
    $customer = User::factory()->create();
    $variant = ProductVariant::factory()->create(['stock' => 10]);

    $this->actingAs($customer)->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
    $this->actingAs($customer)->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 3]);

    expect($customer->cart->items()->sole()->quantity)->toBe(5);
});

test('the cart quantity cannot exceed stock or the per-item maximum', function (int $stock, int $quantity) {
    $customer = User::factory()->create();
    $variant = ProductVariant::factory()->create(['stock' => $stock]);

    $this->actingAs($customer)
        ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => $quantity])
        ->assertSessionHasErrors('quantity');

    expect(CartItem::count())->toBe(0);
})->with([
    'more than stock' => [3, 4],
    'more than the maximum of 10' => [50, 11],
]);

test('a draft product cannot be added to the cart', function () {
    $variant = ProductVariant::factory()->create();
    $variant->product->update(['status' => 'draft']);

    $this->actingAs(User::factory()->create())
        ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
        ->assertSessionHasErrors('product_variant_id');
});

test('a customer cannot change another customer\'s cart item', function () {
    $owner = User::factory()->create();
    addToCart($owner, ProductVariant::factory()->create(), 1);
    $item = $owner->cart->items()->sole();

    $this->actingAs(User::factory()->create())
        ->patch(route('cart.items.update', $item), ['quantity' => 2])
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->delete(route('cart.items.destroy', $item))
        ->assertNotFound();

    expect($item->fresh()->quantity)->toBe(1);
});
