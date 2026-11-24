<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\Vite;

test('responses carry the security headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('pages send a nonce based content security policy that the inline script follows', function () {
    config(['shop.security.content_security_policy' => true]);

    $response = $this->get(route('home'))->assertOk();
    $nonce = Vite::cspNonce();

    expect($nonce)->not->toBeEmpty();
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("script-src 'self' 'nonce-{$nonce}'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'");
    $response->assertSee('<script nonce="'.$nonce.'">', false);
});

test('the content security policy can be switched off for the vite dev server', function () {
    config(['shop.security.content_security_policy' => false]);

    $this->get(route('home'))->assertHeaderMissing('Content-Security-Policy');
});

test('an unexpected appearance cookie value never reaches the page script', function () {
    $this->withUnencryptedCookie('appearance', "dark';alert(1)//")
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('alert(1)', false)
        ->assertSee("const appearance = 'system';", false);
});

test('https requests forwarded by a trusted proxy get strict transport security', function () {
    config(['trustedproxy.proxies' => '10.0.0.1']);

    // Untrusted first: the test client builds later URLs from the previous request's scheme.
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeader('X-Forwarded-Proto', 'https')
        ->get(route('home'))
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeader('X-Forwarded-Proto', 'https')
        ->get(route('home'))
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
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
