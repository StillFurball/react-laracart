<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\ResolveFlashSalePrices;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public const SORTS = ['newest', 'bestselling', 'price_asc', 'price_desc'];

    /**
     * Product listing with search, category filter, sorting and pagination.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTS)],
        ]);

        $category = filled($filters['category'] ?? null)
            ? Category::where('slug', $filters['category'])->first()
            : null;
        $search = $filters['q'] ?? null;
        $sort = $filters['sort'] ?? 'newest';

        $products = self::cardQuery()
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where('name', 'like', $term)
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', $term));
            }))
            ->when($category, fn (Builder $query) => $query->whereIn('category_id', $category->selfAndDescendantIds()))
            ->when($sort === 'bestselling', fn (Builder $query) => $query
                ->withSum(['orderItems as sold_count' => fn (Builder $items) => $items
                    ->whereHas('order', fn (Builder $orders) => $orders->whereNotIn('status', OrderStatus::voidStatuses())),
                ], 'quantity')
                ->orderByDesc('sold_count'))
            ->when($sort === 'price_asc', fn (Builder $query) => $query->orderBy('min_price'))
            ->when($sort === 'price_desc', fn (Builder $query) => $query->orderByDesc('min_price'))
            ->latest('id')
            ->paginate((int) config('shop.products_per_page'))
            ->withQueryString()
            ->through(fn (Product $product): array => self::card($product));

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => Category::whereNull('parent_id')
                ->with('children.children')
                ->orderBy('name')
                ->get(),
            'filters' => [
                'q' => $search ?? '',
                'category' => $category->slug ?? '',
                'sort' => $sort,
            ],
            'activeCategory' => $category?->only(['name', 'slug']),
        ])->withViewData('seo', [
            'title' => $category->name ?? 'Semua barang',
            'description' => $category !== null
                ? "Belanja {$category->name} di ".config('app.name').': '.$products->total().' barang, harga jelas, ongkir per kilo.'
                : null,
            // One canonical URL per listing; sort order and search terms only re-order or filter it.
            'canonical' => route('products.index', array_filter(['category' => $category?->slug])),
            'noindex' => filled($search),
        ]);
    }

    /**
     * Product detail page with variant picker, flash sale offers and related products.
     */
    public function show(Product $product, ResolveFlashSalePrices $flashSalePrices): Response
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load(['category', 'images', 'variants' => fn ($query) => $query->orderBy('id')]);

        $variantIds = $product->variants->pluck('id')->all();
        $offers = $flashSalePrices->offersFor($variantIds);
        $nextOffer = FlashSaleItem::query()
            ->whereIn('product_variant_id', $variantIds)
            ->whereHas('flashSale', fn (Builder $sales) => $sales->upcoming())
            ->with('flashSale')
            ->get()
            ->sortBy(fn (FlashSaleItem $offer): int => $offer->flashSale->starts_at->getTimestamp())
            ->first();

        $related = self::cardQuery()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(fn (Product $related): array => self::card($related));

        return Inertia::render('shop/product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'weight_gram' => $product->weight_gram,
                'category' => $product->category->only(['name', 'slug']),
                'images' => $product->images->map->only(['id', 'url']),
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'label' => $variant->label(),
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'stock' => $variant->stock,
                    'is_low_stock' => $variant->isLowStock(),
                    'flash_sale' => self::offer($offers->get($variant->id), $variant),
                ]),
                'upcoming_flash_sale' => $nextOffer === null ? null : [
                    'name' => $nextOffer->flashSale->name,
                    'starts_at' => $nextOffer->flashSale->starts_at->toIso8601String(),
                    'price' => $nextOffer->sale_price,
                ],
            ],
            'related' => $related,
            'maxPerItem' => (int) config('shop.max_quantity_per_item'),
        ])->withViewData('seo', self::productSeo($product, $offers));
    }

    /**
     * Share preview and schema.org Product data, with the prices a shopper pays right now.
     *
     * @param  Collection<int, FlashSaleItem>  $offers
     * @return array<string, mixed>
     */
    private static function productSeo(Product $product, Collection $offers): array
    {
        $url = route('products.show', $product);
        $prices = $product->variants->map(fn (ProductVariant $variant): int => $offers->get($variant->id)->sale_price ?? $variant->price);
        $inStock = $product->variants->contains(fn (ProductVariant $variant): bool => $variant->stock > 0);
        $description = Str::of($product->description ?? '')->stripTags()->squish()->limit(160)->toString();

        return [
            'title' => $product->name,
            'description' => $description !== ''
                ? $description
                : "{$product->name} mulai ".Voucher::rupiah((int) $prices->min()).' di '.config('app.name').'.',
            'canonical' => $url,
            'type' => 'product',
            'image' => $product->images->first()?->url,
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'description' => $description !== '' ? $description : $product->name,
                'sku' => $product->variants->first()?->sku,
                'category' => $product->category->name,
                'image' => $product->images->pluck('url')->all(),
                'url' => $url,
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => 'IDR',
                    'lowPrice' => (int) $prices->min(),
                    'highPrice' => (int) $prices->max(),
                    'offerCount' => $product->variants->count(),
                    'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url' => $url,
                ],
            ],
        ];
    }

    /**
     * @return array{name: string, price: int, discount_percent: int, ends_at: string, quota: int|null, sold: int, remaining: int|null, per_user_limit: int|null}|null
     */
    private static function offer(?FlashSaleItem $offer, ProductVariant $variant): ?array
    {
        return $offer === null ? null : [
            'name' => $offer->flashSale->name,
            'price' => $offer->sale_price,
            'discount_percent' => $offer->discountPercent($variant->price),
            'ends_at' => $offer->flashSale->ends_at->toIso8601String(),
            'quota' => $offer->quota,
            'sold' => $offer->sold_count,
            'remaining' => $offer->remainingQuota(),
            'per_user_limit' => $offer->per_user_limit,
        ];
    }

    /**
     * @return Builder<Product>
     */
    public static function cardQuery(): Builder
    {
        return Product::active()
            ->whereHas('variants')
            ->with([
                'images',
                'category',
                'variants:id,product_id,attributes,price',
                'variants.flashSaleItems' => fn ($offers) => $offers
                    ->whereHas('flashSale', fn (Builder $sales) => $sales->running())
                    ->with('flashSale:id,ends_at'),
            ])
            ->withMin('variants as min_price', 'price')
            ->withSum('variants as total_stock', 'stock')
            ->withExists(['variants as has_low_stock' => fn (Builder $query) => $query
                ->where('stock', '>', 0)
                ->whereColumn('stock', '<=', 'low_stock_threshold')]);
    }

    /**
     * @return array{id: int, name: string, slug: string, category: string, image: string|null, min_price: int, total_stock: int, has_low_stock: bool, weight_gram: int, colors: list<string>, flash_sale: array{price: int, regular_price: int, discount_percent: int, ends_at: string}|null}
     */
    public static function card(Product $product): array
    {
        $colors = [];

        foreach ($product->variants as $variant) {
            $color = $variant->getAttribute('attributes')['Warna'] ?? null;

            if (is_string($color) && $color !== '' && ! in_array($color, $colors, true)) {
                $colors[] = $color;
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category->name,
            'image' => $product->images->first()?->url,
            // Aggregates added by cardQuery() (withMin / withSum / withExists), not model attributes.
            'min_price' => (int) $product->getAttribute('min_price'),
            'total_stock' => (int) $product->getAttribute('total_stock'),
            'has_low_stock' => (bool) $product->getAttribute('has_low_stock'),
            'weight_gram' => $product->weight_gram,
            'colors' => $colors,
            'flash_sale' => self::bestOffer($product),
        ];
    }

    /**
     * The cheapest running flash sale offer among the product's variants (needs cardQuery's eager loads).
     *
     * @return array{price: int, regular_price: int, discount_percent: int, ends_at: string}|null
     */
    private static function bestOffer(Product $product): ?array
    {
        $best = $product->variants
            ->flatMap(fn (ProductVariant $variant) => $variant->flashSaleItems
                ->filter(fn (FlashSaleItem $offer): bool => $offer->sale_price < $variant->price)
                ->map(fn (FlashSaleItem $offer): array => ['offer' => $offer, 'variant' => $variant]))
            ->sortBy(fn (array $candidate): int => $candidate['offer']->sale_price)
            ->first();

        if ($best === null) {
            return null;
        }

        ['offer' => $offer, 'variant' => $variant] = $best;

        return [
            'price' => $offer->sale_price,
            'regular_price' => $variant->price,
            'discount_percent' => $offer->discountPercent($variant->price),
            'ends_at' => $offer->flashSale->ends_at->toIso8601String(),
        ];
    }
}
