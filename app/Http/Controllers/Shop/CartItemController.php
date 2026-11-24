<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\ResolveFlashSalePrices;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CartItemController extends Controller
{
    /**
     * Add a variant to the cart, merging with an existing line. The price the customer just saw
     * (flash sale price included) becomes the reference for later price-change notices.
     */
    public function store(Request $request, ResolveFlashSalePrices $flashSalePrices): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::findOrFail($request->integer('product_variant_id'));

        if (! $variant->isPurchasable()) {
            throw ValidationException::withMessages(['product_variant_id' => 'Produk tidak tersedia.']);
        }

        $cart = $request->user()->cart()->firstOrCreate();
        $item = $cart->items()->firstWhere('product_variant_id', $variant->id);
        $quantity = ($item->quantity ?? 0) + (int) $validated['quantity'];

        $this->ensureQuantityAllowed($variant, $quantity);

        $cart->items()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            ['quantity' => $quantity, 'price_at_add' => $flashSalePrices->priceFor($variant, $quantity, $request->user()->id)['unit_price']],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk ditambahkan ke keranjang.']);

        return back();
    }

    /**
     * Change the quantity of a cart line. A quantity can move the line in or out of a flash sale
     * price; the cart explains that itself, so it is not announced as a price change.
     */
    public function update(Request $request, CartItem $cartItem, ResolveFlashSalePrices $flashSalePrices): RedirectResponse
    {
        $this->ensureOwnedBy($request, $cartItem);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = (int) $validated['quantity'];

        $this->ensureQuantityAllowed($cartItem->variant, $quantity);

        $cartItem->update([
            'quantity' => $quantity,
            'price_at_add' => $flashSalePrices->priceFor($cartItem->variant, $quantity, $request->user()->id)['unit_price'],
        ]);

        return back();
    }

    /**
     * Remove a cart line.
     */
    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->ensureOwnedBy($request, $cartItem);

        $cartItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produk dihapus dari keranjang.']);

        return back();
    }

    private function ensureOwnedBy(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 404);
    }

    private function ensureQuantityAllowed(ProductVariant $variant, int $quantity): void
    {
        $maxPerItem = (int) config('shop.max_quantity_per_item');

        if ($quantity > $variant->stock) {
            throw ValidationException::withMessages(['quantity' => "Stok tersisa {$variant->stock}."]);
        }

        if ($quantity > $maxPerItem) {
            throw ValidationException::withMessages(['quantity' => "Maksimal {$maxPerItem} per produk."]);
        }
    }
}
