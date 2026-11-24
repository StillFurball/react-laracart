<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlashSaleRequest;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    /**
     * Sales newest schedule first, with units sold (incl. unpaid reservations) and paid revenue.
     */
    public function index(): Response
    {
        $sales = FlashSale::query()
            ->withCount('items')
            ->withSum('items as sold_total', 'sold_count')
            ->addSelect(['revenue' => OrderItem::query()
                ->selectRaw('coalesce(sum(order_items.line_total), 0)')
                ->join('flash_sale_items', 'flash_sale_items.id', '=', 'order_items.flash_sale_item_id')
                ->whereColumn('flash_sale_items.flash_sale_id', 'flash_sales.id')
                ->whereHas('order', fn (Builder $orders) => $orders->whereIn('status', OrderStatus::paidStatuses()))])
            ->latest('starts_at')
            ->latest('id')
            ->paginate(15)
            ->through(fn (FlashSale $sale): array => [
                ...self::formData($sale),
                'id' => $sale->id,
                'status' => $sale->status()->value,
                'status_label' => $sale->status()->label(),
                'items_count' => $sale->items_count,
                // Query aliases (withSum / addSelect), not model attributes.
                'sold_total' => (int) $sale->getAttribute('sold_total'),
                'revenue' => (int) $sale->getAttribute('revenue'),
            ]);

        return Inertia::render('admin/flash-sales/index', [
            'flashSales' => $sales,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/flash-sales/form', [
            'flashSale' => null,
            'items' => [],
            'variantOptions' => [],
            'filters' => ['variant_q' => ''],
        ]);
    }

    public function store(FlashSaleRequest $request): RedirectResponse
    {
        $flashSale = FlashSale::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Flash sale dibuat. Tambahkan barang yang dijual.']);

        return to_route('admin.flash-sales.edit', $flashSale);
    }

    /**
     * Edit page: schedule, offers and a variant search (?variant_q=) for adding offers.
     */
    public function edit(Request $request, FlashSale $flashSale): Response
    {
        $filters = $request->validate([
            'variant_q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = $filters['variant_q'] ?? null;

        return Inertia::render('admin/flash-sales/form', [
            'flashSale' => [
                ...self::formData($flashSale),
                'id' => $flashSale->id,
                'status' => $flashSale->status()->value,
                'status_label' => $flashSale->status()->label(),
            ],
            'items' => $flashSale->items()
                ->with('variant.product')
                ->withExists('orderItems as has_orders')
                ->get()
                ->map(fn (FlashSaleItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->variant->product->name,
                    'variant_label' => $item->variant->label(),
                    'sku' => $item->variant->sku,
                    'regular_price' => $item->variant->price,
                    'sale_price' => $item->sale_price,
                    'quota' => $item->quota,
                    'sold_count' => $item->sold_count,
                    'per_user_limit' => $item->per_user_limit,
                    'stock' => $item->variant->stock,
                    'is_available' => $item->variant->isPurchasable(),
                    'has_orders' => (bool) $item->getAttribute('has_orders'),
                ]),
            'variantOptions' => ProductVariant::query()
                ->with('product:id,name,status')
                ->whereHas('product')
                ->whereNotIn('id', $flashSale->items()->select('product_variant_id'))
                ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                    $term = '%'.addcslashes($search, '%_\\').'%';

                    $query->where('sku', 'like', $term)
                        ->orWhereHas('product', fn (Builder $products) => $products->where('name', 'like', $term));
                }))
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'label' => $variant->product->name.($variant->label() ? " ({$variant->label()})" : '').' · '.$variant->sku,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                ]),
            'filters' => ['variant_q' => $search ?? ''],
        ]);
    }

    public function update(FlashSaleRequest $request, FlashSale $flashSale): RedirectResponse
    {
        $flashSale->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Flash sale diperbarui.']);

        return to_route('admin.flash-sales.edit', $flashSale);
    }

    /**
     * A sale that already sold something stays for the order history; switch it off instead.
     */
    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        if (OrderItem::whereIn('flash_sale_item_id', $flashSale->items()->select('id'))->exists()) {
            throw ValidationException::withMessages(['flash_sale' => 'Flash sale ini sudah punya pesanan; nonaktifkan saja.']);
        }

        $flashSale->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Flash sale dihapus.']);

        return to_route('admin.flash-sales.index');
    }

    /**
     * @return array{name: string, starts_at: string, ends_at: string, is_active: bool}
     */
    private static function formData(FlashSale $flashSale): array
    {
        return [
            'name' => $flashSale->name,
            'starts_at' => $flashSale->starts_at->format('Y-m-d\TH:i'),
            'ends_at' => $flashSale->ends_at->format('Y-m-d\TH:i'),
            'is_active' => $flashSale->is_active,
        ];
    }
}
