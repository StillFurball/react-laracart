<?php

namespace App\Actions\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\FlashSaleItem;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Prices a cart from current variant data (flash sale prices included) and computes checkout totals.
 */
class SummarizeCart
{
    /**
     * Unknown voucher codes a customer may try before lookups pause for the decay window.
     */
    public const VOUCHER_GUESSES = 10;

    public const VOUCHER_GUESS_DECAY_SECONDS = 600;

    public function __construct(private ResolveFlashSalePrices $flashSalePrices) {}

    /**
     * Each returned item carries computed attributes (unit_price, line_total, issue, sale_notice);
     * "offers" maps a cart item id to the flash sale offer its price comes from.
     *
     * @return array{items: Collection<int, CartItem>, offers: array<int, FlashSaleItem>, subtotal: int, weight_gram: int, issues: list<string>, price_changes: list<string>}
     */
    public function handle(?Cart $cart): array
    {
        $items = $cart?->items()->with(['variant.product.images'])->get() ?? new Collection;
        $maxPerItem = (int) config('shop.max_quantity_per_item');
        $issues = [];
        $priceChanges = [];
        $appliedOffers = [];

        $offers = $this->flashSalePrices->offersFor($items->pluck('product_variant_id')->all());
        $purchased = $cart !== null ? $this->flashSalePrices->purchasedBy($cart->user_id, $offers->pluck('id')->all()) : [];

        foreach ($items as $item) {
            $variant = $item->variant;
            $name = $variant->product->name.($variant->label() ? " ({$variant->label()})" : '');
            $offer = $offers->get($variant->id);

            ['unit_price' => $unitPrice, 'offer' => $appliedOffer, 'notice' => $saleNotice] = ResolveFlashSalePrices::decide(
                $variant,
                $item->quantity,
                $offer,
                $offer !== null ? ($purchased[$offer->id] ?? 0) : 0,
            );

            $issue = match (true) {
                ! $variant->isPurchasable() => 'Produk tidak lagi tersedia.',
                $variant->stock === 0 => 'Stok habis.',
                $item->quantity > $variant->stock => "Stok tersisa {$variant->stock}.",
                $item->quantity > $maxPerItem => "Maksimal {$maxPerItem} per produk.",
                default => null,
            };

            $item->setAttribute('issue', $issue);
            $item->setAttribute('unit_price', $unitPrice);
            $item->setAttribute('line_total', $unitPrice * $item->quantity);
            $item->setAttribute('sale_notice', $saleNotice);

            if ($appliedOffer !== null) {
                $appliedOffers[$item->id] = $appliedOffer;
            }

            if ($issue !== null) {
                $issues[] = "{$name}: {$issue}";
            }

            if ($item->price_at_add !== $unitPrice) {
                $priceChanges[] = sprintf(
                    'Harga %s berubah dari %s menjadi %s.',
                    $name,
                    Voucher::rupiah($item->price_at_add),
                    Voucher::rupiah($unitPrice),
                );
            }
        }

        return [
            'items' => $items,
            'offers' => $appliedOffers,
            'subtotal' => (int) $items->sum(fn (CartItem $item): int => (int) $item->getAttribute('line_total')),
            'weight_gram' => (int) $items->sum(fn (CartItem $item): int => $item->variant->weight_gram * $item->quantity),
            'issues' => $issues,
            'price_changes' => $priceChanges,
        ];
    }

    /**
     * Page props for a priced cart item (call after handle()).
     *
     * @return array{id: int, quantity: int, product_name: string, product_slug: string, variant_label: string|null, sku: string, price: int, regular_price: int, line_total: int, stock: int, issue: string|null, sale_notice: string|null, flash_sale: array{ends_at: string, discount_percent: int}|null, image: string|null}
     */
    public static function itemProps(CartItem $item, ?FlashSaleItem $offer = null): array
    {
        $variant = $item->variant;

        return [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'product_name' => $variant->product->name,
            'product_slug' => $variant->product->slug,
            'variant_label' => $variant->label(),
            'sku' => $variant->sku,
            'price' => (int) $item->getAttribute('unit_price'),
            'regular_price' => $variant->price,
            'line_total' => (int) $item->getAttribute('line_total'),
            'stock' => $variant->stock,
            'issue' => $item->getAttribute('issue'),
            'sale_notice' => $item->getAttribute('sale_notice'),
            'flash_sale' => $offer === null ? null : [
                'ends_at' => $offer->flashSale->ends_at->toIso8601String(),
                'discount_percent' => $offer->discountPercent($variant->price),
            ],
            'image' => $variant->product->images->first()?->url,
        ];
    }

    /**
     * Mark the prices just shown as seen so a change is only announced once. Updates by query: the
     * summary items carry computed attributes that must never be saved.
     *
     * @param  Collection<int, CartItem>  $items  items priced by handle()
     */
    public function acknowledgePriceChanges(Collection $items): void
    {
        foreach ($items as $item) {
            $unitPrice = (int) $item->getAttribute('unit_price');

            if ($item->price_at_add !== $unitPrice) {
                CartItem::whereKey($item->id)->update(['price_at_add' => $unitPrice]);
            }
        }
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
            ['voucher' => $voucher, 'error' => $voucherError] = $this->findVoucher($user, $voucherCode);
            $voucherError ??= $voucher?->rejectionReason($user, $subtotal);

            if ($voucher !== null && $voucherError === null) {
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
     * Look a voucher code up for a customer. Unknown codes count against a per-customer limit, so
     * private codes cannot be found by trying many guesses in a row.
     *
     * @return array{voucher: Voucher|null, error: string|null}
     */
    public function findVoucher(User $user, string $code): array
    {
        $throttleKey = 'voucher-guesses:'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, self::VOUCHER_GUESSES)) {
            return ['voucher' => null, 'error' => 'Terlalu banyak percobaan kode voucher. Coba lagi dalam beberapa menit.'];
        }

        $voucher = Voucher::where('code', Str::upper(trim($code)))->first();

        if ($voucher === null) {
            RateLimiter::hit($throttleKey, self::VOUCHER_GUESS_DECAY_SECONDS);

            return ['voucher' => null, 'error' => 'Kode voucher tidak ditemukan.'];
        }

        return ['voucher' => $voucher, 'error' => null];
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
