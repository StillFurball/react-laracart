<?php

namespace App\Actions\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Prices a cart from current variant data and computes checkout totals.
 */
class SummarizeCart
{
    /**
     * @return array{items: Collection<int, CartItem>, subtotal: int, weight_gram: int, issues: list<string>, price_changes: list<string>}
     */
    public function handle(?Cart $cart): array
    {
        $items = $cart?->items()->with(['variant.product.images'])->get() ?? new Collection;
        $maxPerItem = (int) config('shop.max_quantity_per_item');
        $issues = [];
        $priceChanges = [];

        foreach ($items as $item) {
            $variant = $item->variant;
            $name = $variant->product->name.($variant->label() ? " ({$variant->label()})" : '');

            $issue = match (true) {
                ! $variant->isPurchasable() => 'Produk tidak lagi tersedia.',
                $variant->stock === 0 => 'Stok habis.',
                $item->quantity > $variant->stock => "Stok tersisa {$variant->stock}.",
                $item->quantity > $maxPerItem => "Maksimal {$maxPerItem} per produk.",
                default => null,
            };

            $item->setAttribute('issue', $issue);
            $item->setAttribute('line_total', $variant->price * $item->quantity);

            if ($issue !== null) {
                $issues[] = "{$name}: {$issue}";
            }

            if ($item->price_at_add !== $variant->price) {
                $priceChanges[] = sprintf(
                    'Harga %s berubah dari %s menjadi %s.',
                    $name,
                    Voucher::rupiah($item->price_at_add),
                    Voucher::rupiah($variant->price),
                );
            }
        }

        return [
            'items' => $items,
            'subtotal' => (int) $items->sum(fn (CartItem $item): int => $item->variant->price * $item->quantity),
            'weight_gram' => (int) $items->sum(fn (CartItem $item): int => $item->variant->weight_gram * $item->quantity),
            'issues' => $issues,
            'price_changes' => $priceChanges,
        ];
    }

    /**
     * Page props for a priced cart item (call after handle()).
     *
     * @return array{id: int, quantity: int, product_name: string, product_slug: string, variant_label: string|null, sku: string, price: int, line_total: int, stock: int, issue: string|null, image: string|null}
     */
    public static function itemProps(CartItem $item): array
    {
        $variant = $item->variant;

        return [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'variant_label' => $variant->label(),
            'sku' => $variant->sku,
            'price' => $variant->price,
            'line_total' => (int) $item->getAttribute('line_total'),
            'stock' => $variant->stock,
            'issue' => $item->getAttribute('issue'),
            'image' => $variant->product->images->first()?->url,
        ];
    }

    /**
     * Mark current prices as seen so a price change is only announced once.
     */
    public function acknowledgePriceChanges(?Cart $cart): void
    {
        $cart?->items()->with('variant')->get()->each(function (CartItem $item): void {
            if ($item->price_at_add !== $item->variant->price) {
                $item->update(['price_at_add' => $item->variant->price]);
            }
        });
    }

    /**
     * @return array{shipping_cost: int, discount: int, voucher: Voucher|null, voucher_error: string|null, grand_total: int}
     */
    public function totals(User $user, int $subtotal, int $weightGram, string $service, ?string $voucherCode): array
    {
        $shippingCost = self::shippingCost($service, $weightGram);
        $voucher = null;
        $voucherError = null;
        $discount = 0;

        if (filled($voucherCode)) {
            $voucher = Voucher::where('code', Str::upper(trim($voucherCode)))->first();
            $voucherError = $voucher === null
                ? 'Kode voucher tidak ditemukan.'
                : $voucher->rejectionReason($user, $subtotal);

            if ($voucherError === null) {
                $discount = $voucher->discountFor($subtotal);
            } else {
                $voucher = null;
            }
        }

        return [
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'voucher' => $voucher,
            'voucher_error' => $voucherError,
            'grand_total' => $subtotal - $discount + $shippingCost,
        ];
    }

    /**
     * @return array<string, array{name: string, rate_per_kg: int, eta: string}>
     */
    public static function shippingServices(): array
    {
        return config('shop.shipping.services');
    }

    /**
     * Cost = total weight rounded up to whole kilograms (minimum 1 kg) x the service rate.
     */
    public static function shippingCost(string $service, int $weightGram): int
    {
        $kilograms = max(1, (int) ceil($weightGram / 1000));

        return $kilograms * self::shippingServices()[$service]['rate_per_kg'];
    }
}
