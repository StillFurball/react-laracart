<?php

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('a customer with orders can delete their account: personal data is erased and the orders are kept', function () {
    $order = Order::factory()->status(OrderStatus::Completed)->create();
    $user = $order->user;
    $user->update(['phone' => '081234567890']);
    Address::factory()->for($user)->create();
    $email = $user->email;

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();

    $user->refresh();
    expect($user->name)->toBe('Akun dihapus');
    expect($user->email)->not->toBe($email);
    expect($user->phone)->toBeNull();
    expect($user->address)->toBeNull();
    expect($order->fresh()->user_id)->toBe($user->id);

    $this->post(route('login.store'), ['email' => $email, 'password' => 'password']);
    $this->assertGuest();
});

test('an account with an order still in progress cannot be deleted yet', function () {
    $order = Order::factory()->status(OrderStatus::Paid)->create();

    $this->actingAs($order->user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors(['account' => 'Selesaikan atau batalkan pesanan yang masih berjalan sebelum menghapus akun.'])
        ->assertRedirect(route('profile.edit'));

    $this->assertAuthenticated();
    expect($order->user->fresh()->email)->toBe($order->user->email);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
