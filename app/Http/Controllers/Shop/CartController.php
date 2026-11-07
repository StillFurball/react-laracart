<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\SummarizeCart;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Show the cart priced with current variant data.
     */
    public function index(Request $request, SummarizeCart $summarizeCart): Response
    {
        $cart = $request->user()->cart;
        $summary = $summarizeCart->handle($cart);
        $summarizeCart->acknowledgePriceChanges($cart);

        return Inertia::render('shop/cart', [
            'items' => $summary['items']->map(fn ($item) => SummarizeCart::itemProps($item)),
            'subtotal' => $summary['subtotal'],
            'issues' => $summary['issues'],
            'priceChanges' => $summary['price_changes'],
            'maxPerItem' => (int) config('shop.max_quantity_per_item'),
        ]);
    }
}
