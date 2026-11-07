<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Shop\AdjustStock;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function store(ProductVariantRequest $request, Product $product, AdjustStock $adjustStock): RedirectResponse
    {
        DB::transaction(function () use ($request, $product, $adjustStock): void {
            $variant = $product->variants()->create($request->variantData());

            if ($request->integer('stock') > 0) {
                $adjustStock->handle($variant, $request->integer('stock'), StockMovementType::In, $request->user(), note: 'Stok awal');
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Varian ditambahkan.']);

        return to_route('admin.products.edit', $product);
    }

    /**
     * Update variant data; stock only changes through stock adjustments.
     */
    public function update(ProductVariantRequest $request, ProductVariant $variant): RedirectResponse
    {
        $variant->update($request->variantData());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Varian diperbarui.']);

        return to_route('admin.products.edit', $variant->product_id);
    }

    /**
     * Soft delete a variant; a product always keeps at least one.
     */
    public function destroy(ProductVariant $variant): RedirectResponse
    {
        if (ProductVariant::where('product_id', $variant->product_id)->count() <= 1) {
            throw ValidationException::withMessages(['variant' => 'Produk minimal memiliki satu varian.']);
        }

        $variant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Varian dihapus.']);

        return to_route('admin.products.edit', $variant->product_id);
    }
}
