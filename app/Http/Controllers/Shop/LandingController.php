<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Landing page: every active product lightest first, with its department and the cheapest variant's original price when it is discounted.
     */
    public function __invoke(): Response
    {
        $products = CatalogController::cardQuery()
            ->with('category.parent.parent')
            ->addSelect(['compare_at_price' => ProductVariant::select('compare_at_price')
                ->whereColumn('product_id', 'products.id')
                ->orderBy('price')
                ->limit(1)])
            ->orderBy('weight_gram')
            ->get()
            ->map(fn (Product $product): array => [
                ...CatalogController::card($product),
                'department' => ($product->category->parent?->parent ?? $product->category->parent ?? $product->category)->name,
                'compare_at_price' => $product->compare_at_price > $product->min_price ? (int) $product->compare_at_price : null,
            ]);

        return Inertia::render('landing', [
            'products' => $products,
        ]);
    }
}
