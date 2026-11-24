<?php

use App\Models\User;

test('visitors get the storefront error page with the real status code', function () {
    config(['app.debug' => false]);

    $this->get('/products/tidak-ada')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('error')
            ->where('status', 404)
            ->has('store.name')
            ->has('menuCategories'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('error')->where('status', 403));
});

test('debug mode keeps the framework error output for developers', function () {
    config(['app.debug' => true]);

    $response = $this->get('/products/tidak-ada')->assertNotFound();

    expect($response->headers->get('X-Inertia'))->toBeNull();
    expect($response->getContent())->not->toContain('"component":"error"');
});

test('an expired session sends the visitor back with a message instead of an error page', function () {
    $this->app['env'] = 'local';
    config(['app.debug' => false]);

    $this->from(route('products.index'))
        ->post(route('login.store'), ['email' => 'a@example.com', 'password' => 'x'])
        ->assertRedirect(route('products.index'))
        ->assertInertiaFlash('toast.message', 'Sesi halaman sudah berakhir. Silakan coba lagi.');
});
