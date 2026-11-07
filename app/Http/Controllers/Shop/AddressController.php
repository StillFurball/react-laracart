<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddressRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    /**
     * Show the customer's single shipping address form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('account/address', [
            'address' => $request->user()->address?->toSnapshot(),
        ]);
    }

    /**
     * Create or update the shipping address, then continue to checkout when the cart has items.
     */
    public function update(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->address()->updateOrCreate([], $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alamat pengiriman disimpan.']);

        return $user->cart?->items()->exists()
            ? to_route('checkout.show')
            : to_route('address.edit');
    }
}
