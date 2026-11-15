<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Landing page: every active product with its weight, the top-level categories, the shipping rates and the store rules.
     */
    public function __invoke(): Response
    {
        $products = CatalogController::cardQuery()
            ->with('category.parent.parent')
            ->orderBy('weight_gram')
            ->get()
            ->map(fn (Product $product): array => [
                ...CatalogController::card($product),
                'department' => ($product->category->parent?->parent ?? $product->category->parent ?? $product->category)->name,
            ]);

        return Inertia::render('landing', [
            'products' => $products,
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(['name', 'slug']),
            'shipping' => [
                'origin' => config('shop.shipping.origin'),
                'services' => collect(config('shop.shipping.services'))
                    ->map(fn (array $service, string $key): array => ['key' => $key, ...$service])
                    ->values(),
            ],
            'rules' => [
                'payment_due_hours' => (int) config('shop.payment_due_hours'),
                'payment_reminder_hours' => (int) config('shop.payment_reminder_hours'),
                'auto_complete_days' => (int) config('shop.auto_complete_days'),
                'max_quantity_per_item' => (int) config('shop.max_quantity_per_item'),
            ],
            'vouchers' => Voucher::where('is_active', true)
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->orderBy('min_purchase')
                ->pluck('code'),
        ]);
    }
}
