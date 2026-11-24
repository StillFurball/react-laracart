<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlashSaleItemRequest;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FlashSaleItemController extends Controller
{
    public function store(FlashSaleItemRequest $request, FlashSale $flashSale): RedirectResponse
    {
        $flashSale->items()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Barang ditambahkan ke flash sale.']);

        return to_route('admin.flash-sales.edit', $flashSale);
    }

    /**
     * Change price and limits; the variant stays (remove the offer and add another to switch).
     */
    public function update(FlashSaleItemRequest $request, FlashSaleItem $flashSaleItem): RedirectResponse
    {
        $flashSaleItem->update($request->safe()->only(['sale_price', 'quota', 'per_user_limit']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Barang flash sale diperbarui.']);

        return to_route('admin.flash-sales.edit', $flashSaleItem->flash_sale_id);
    }

    /**
     * An offer that was ordered stays for the order history; set its quota to what was sold instead.
     */
    public function destroy(FlashSaleItem $flashSaleItem): RedirectResponse
    {
        if ($flashSaleItem->orderItems()->exists()) {
            throw ValidationException::withMessages(['item' => 'Barang ini sudah dipesan dengan harga flash sale; turunkan kuotanya saja.']);
        }

        $flashSaleItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Barang dihapus dari flash sale.']);

        return to_route('admin.flash-sales.edit', $flashSaleItem->flash_sale_id);
    }
}
