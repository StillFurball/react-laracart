<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Shop\AdjustStock;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockAdjustmentController extends Controller
{
    /**
     * Manual stock adjustment; a reason is mandatory and recorded with the admin.
     */
    public function store(Request $request, ProductVariant $variant, AdjustStock $adjustStock): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['required', 'string', 'max:255'],
        ], [
            'quantity.not_in' => 'Jumlah penyesuaian tidak boleh 0.',
            'note.required' => 'Alasan penyesuaian wajib diisi.',
        ]);

        $adjustStock->handle($variant, (int) $validated['quantity'], StockMovementType::Adjustment, $request->user(), note: $validated['note']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Stok {$variant->sku} sekarang {$variant->stock}."]);

        return back();
    }
}
