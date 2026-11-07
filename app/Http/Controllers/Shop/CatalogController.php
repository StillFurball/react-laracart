<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
                'category' => $category?->slug ?? '',
                'sort' => $sort,
            ],
            'activeCategory' => $category?->only(['name', 'slug']),
        ]);
    }

    /**
     * Product detail page with variant picker and related products.
     */
    public function show(Product $product): Response
    {
        abort_unless($product->status === ProductStatus::Active, 404);

        $product->load(['category', 'images', 'variants' => fn ($query) => $query->orderBy('id')]);

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
                ]),
            ],
            'related' => $related,
            'maxPerItem' => (int) config('shop.max_quantity_per_item'),
        ]);
    }

    /**
     * @return Builder<Product>
     */
    private static function cardQuery(): Builder
    {
        return Product::active()
            ->whereHas('variants')
            ->with(['images', 'category'])
            ->withMin('variants as min_price', 'price')
            ->withSum('variants as total_stock', 'stock')
            ->withExists(['variants as has_low_stock' => fn (Builder $query) => $query
                ->where('stock', '>', 0)
                ->whereColumn('stock', '<=', 'low_stock_threshold')]);
    }

    /**
     * @return array{id: int, name: string, slug: string, category: string, image: string|null, min_price: int, total_stock: int, has_low_stock: bool}
     */
    private static function card(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category->name,
            'image' => $product->images->first()?->url,
            'min_price' => (int) $product->min_price,
            'total_stock' => (int) $product->total_stock,
            'has_low_stock' => (bool) $product->has_low_stock,
        ];
    }
}
