<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('customers are redirected to the storefront', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertRedirect(route('home'));
});

test('admins are redirected to the admin order list', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))->assertRedirect(route('admin.orders.index'));
});
