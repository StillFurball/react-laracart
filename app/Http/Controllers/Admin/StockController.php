<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    /**
     * Low-stock variants and the stock ledger (filterable by SKU).
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'sku' => ['nullable', 'string', 'max:64'],
        ]);

        $lowStock = ProductVariant::with('product:id,name')
            ->whereHas('product')
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->orderBy('stock')
            ->limit(50)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'label' => $variant->label(),
                'sku' => $variant->sku,
                'stock' => $variant->stock,
                'low_stock_threshold' => $variant->low_stock_threshold,
            ]);

        $movements = StockMovement::with(['variant.product:id,name', 'user:id,name', 'order:id,order_number'])
            ->when($filters['sku'] ?? null, fn (Builder $query, string $sku) => $query
                ->whereHas('variant', fn (Builder $variants) => $variants->withTrashed()->where('sku', 'like', '%'.addcslashes($sku, '%_\\').'%')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'sku' => $movement->variant->sku,
                'product_name' => $movement->variant->product->name,
                'type' => $movement->type->value,
                'quantity' => $movement->quantity,
                'stock_after' => $movement->stock_after,
                'order_number' => $movement->order?->order_number,
                'user' => $movement->user?->name ?? 'Sistem',
                'note' => $movement->note,
                'created_at' => $movement->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/stock/index', [
            'lowStock' => $lowStock,
            'movements' => $movements,
            'filters' => ['sku' => $filters['sku'] ?? ''],
        ]);
    }
}
