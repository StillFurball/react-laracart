<?php

namespace App\Http\Controllers\Shop;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    /**
     * Flash sales running now (with every offer) and the ones coming up (as a teaser).
     */
    public function index(): Response
    {
        return Inertia::render('shop/flash-sale', [
            'running' => FlashSale::running()->orderBy('ends_at')->get()
                ->map(fn (FlashSale $sale): array => self::summary($sale))
                ->filter(fn (array $sale): bool => $sale['items'] !== [])
                ->values(),
            'upcoming' => FlashSale::upcoming()->orderBy('starts_at')->limit(3)->get()
                ->map(fn (FlashSale $sale): array => self::summary($sale, limit: 6))
                ->filter(fn (array $sale): bool => $sale['items'] !== [])
                ->values(),
        ])->withViewData('seo', [
            'title' => 'Flash Sale',
            'description' => 'Flash sale '.config('app.name').': harga khusus untuk waktu terbatas, selama kuota masih ada.',
        ]);
    }

    /**
     * The sale to feature on the landing page: the running one that ends first, else the next one.
     *
     * @return array<string, mixed>|null
     */
    public static function featured(): ?array
    {
        $sale = FlashSale::running()->orderBy('ends_at')->first()
            ?? FlashSale::upcoming()->orderBy('starts_at')->first();

        $summary = $sale ? self::summary($sale, limit: 10) : null;

        return $summary !== null && $summary['items'] !== [] ? $summary : null;
    }

    /**
     * A sale with the offers a shopper can see: active, not deleted products, cheaper than normal.
     *
     * @return array{id: int, name: string, status: string, starts_at: string, ends_at: string, items: list<array<string, mixed>>}
     */
    public static function summary(FlashSale $sale, ?int $limit = null): array
    {
        $items = FlashSaleItem::query()
            ->whereBelongsTo($sale)
            ->whereHas('variant', fn (Builder $variants) => $variants
                ->whereNull('product_variants.deleted_at')
                ->whereColumn('flash_sale_items.sale_price', '<', 'product_variants.price')
                ->whereHas('product', fn (Builder $products) => $products
                    ->whereNull('products.deleted_at')
                    ->where('status', ProductStatus::Active)))
            ->with(['variant.product.images', 'variant.product.category'])
            ->orderBy('id')
            ->when($limit, fn (Builder $query, int $limit) => $query->limit($limit))
            ->get();

        return [
            'id' => $sale->id,
            'name' => $sale->name,
            'status' => $sale->status()->value,
            'starts_at' => $sale->starts_at->toIso8601String(),
            'ends_at' => $sale->ends_at->toIso8601String(),
            'items' => array_values($items->map(fn (FlashSaleItem $item): array => self::item($item))->all()),
        ];
    }

    /**
     * @return array{id: int, product_name: string, product_slug: string, category: string, image: string|null, variant_label: string|null, price: int, regular_price: int, discount_percent: int, quota: int|null, sold: int, remaining: int|null, stock: int}
     */
    private static function item(FlashSaleItem $item): array
    {
        $variant = $item->variant;
        $product = $variant->product;

        return [
            'id' => $item->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'category' => $product->category->name,
            'image' => $product->images->first()?->url,
            'variant_label' => $variant->label(),
            'price' => $item->sale_price,
            'regular_price' => $variant->price,
            'discount_percent' => $item->discountPercent($variant->price),
            'quota' => $item->quota,
            'sold' => $item->sold_count,
            'remaining' => $item->remainingQuota(),
            'stock' => $variant->stock,
        ];
    }
}
