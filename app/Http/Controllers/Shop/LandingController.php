<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Products scanned for the "best deals" row; the page shows the five biggest discounts among them.
     */
    private const DEAL_CANDIDATES = 60;

    /**
     * Landing page: the featured flash sale, departments, best compare-at deals and light everyday items.
     * Every query is bounded, so the page stays fast however large the catalogue grows.
     */
    public function __invoke(): Response
    {
        $deals = $this->deals();

        return Inertia::render('landing', [
            'flashSale' => FlashSaleController::featured(),
            'departments' => $this->departments(),
            'deals' => $deals,
            'essentials' => CatalogController::cardQuery()
                ->whereKeyNot(array_column($deals, 'id'))
                ->orderBy('weight_gram')
                ->orderBy('id')
                ->limit(6)
                ->get()
                ->map(fn (Product $product): array => CatalogController::card($product))
                ->values(),
        ])->withViewData('seo', [
            'canonical' => route('home'),
            // Lets search engines show a search box for the store and know who runs it.
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => config('app.name'),
                    'url' => route('home'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('products.index').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Store',
                    'name' => config('app.name'),
                    'url' => route('home'),
                    'email' => config('shop.store.email'),
                    'telephone' => config('shop.store.phone'),
                    'address' => config('shop.store.address'),
                ],
            ],
        ]);
    }

    /**
     * Products whose cheapest variant has a compare-at price above it, biggest discount first.
     *
     * @return list<array<string, mixed>>
     */
    private function deals(): array
    {
        // Both values are query aliases (withMin / addSelect), not model attributes.
        $prices = fn (Product $product): array => [
            (int) $product->getAttribute('min_price'),
            (int) $product->getAttribute('compare_at_price'),
        ];

        return array_values(CatalogController::cardQuery()
            ->whereHas('variants', fn (Builder $variants) => $variants->whereColumn('compare_at_price', '>', 'price'))
            ->addSelect(['compare_at_price' => ProductVariant::select('compare_at_price')
                ->whereColumn('product_id', 'products.id')
                ->orderBy('price')
                ->limit(1)])
            ->latest('id')
            ->limit(self::DEAL_CANDIDATES)
            ->get()
            ->filter(function (Product $product) use ($prices): bool {
                [$price, $compareAt] = $prices($product);

                return $compareAt > $price;
            })
            ->sortByDesc(function (Product $product) use ($prices): float {
                [$price, $compareAt] = $prices($product);

                return 1 - $price / $compareAt;
            })
            ->take(5)
            ->map(fn (Product $product): array => [
                ...CatalogController::card($product),
                'compare_at_price' => $prices($product)[1],
            ])
            ->all());
    }

    /**
     * Top-level categories that have products: lowest price, product count and a lead product to picture.
     *
     * @return list<array{name: string, slug: string, min_price: int, product_count: int, lead_name: string, image: string|null}>
     */
    private function departments(): array
    {
        $categories = Category::get(['id', 'parent_id', 'name', 'slug'])->keyBy('id');

        $rootIdOf = function (int $categoryId) use ($categories): int {
            $category = $categories[$categoryId];

            while ($category->parent_id !== null && $categories->has($category->parent_id)) {
                $category = $categories[$category->parent_id];
            }

            return $category->id;
        };

        $byRoot = Product::active()
            ->join('product_variants', 'product_variants.product_id', '=', 'products.id')
            ->whereNull('product_variants.deleted_at')
            ->groupBy('products.category_id')
            ->selectRaw('products.category_id, min(product_variants.price) as min_price, count(distinct products.id) as product_count')
            ->toBase()
            ->get()
            ->groupBy(fn (object $row): int => $rootIdOf((int) $row->category_id));

        return array_values($categories
            ->filter(fn (Category $category): bool => $category->parent_id === null && $byRoot->has($category->id))
            ->sortBy('name')
            ->map(function (Category $root) use ($byRoot): array {
                $rows = $byRoot[$root->id];
                $lead = CatalogController::cardQuery()
                    ->whereIn('category_id', $root->selfAndDescendantIds())
                    ->orderByRaw('exists (select 1 from product_images where product_images.product_id = products.id) desc')
                    ->latest('id')
                    ->first();

                return [
                    'name' => $root->name,
                    'slug' => $root->slug,
                    'min_price' => (int) $rows->min('min_price'),
                    'product_count' => (int) $rows->sum('product_count'),
                    'lead_name' => $lead->name ?? $root->name,
                    'image' => $lead?->images->first()?->url,
                ];
            })
            ->all());
    }
}
