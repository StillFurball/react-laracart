<?php

namespace App\Actions\Shop;

use App\Enums\OrderStatus;
use App\Models\FlashSaleItem;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Decides what a customer pays for a variant while flash sales run. A cart line gets the sale price
 * only when its whole quantity fits in the remaining quota and in the customer's own limit;
 * otherwise the normal price applies and the line explains how to get the sale price.
 */
class ResolveFlashSalePrices
{
    /**
     * Running offers keyed by variant id. Offers that are not cheaper than the variant are ignored.
     *
     * @param  array<int, int>  $variantIds
     * @return Collection<int, FlashSaleItem>
     */
    public function offersFor(array $variantIds): Collection
    {
        if ($variantIds === []) {
            return new Collection;
        }

        return FlashSaleItem::query()
            ->with(['flashSale', 'variant'])
            ->whereIn('product_variant_id', $variantIds)
            ->whereHas('flashSale', fn (Builder $query) => $query->running())
            ->get()
            ->filter(fn (FlashSaleItem $offer): bool => $offer->sale_price < $offer->variant->price)
            // Overlapping sales are refused when saved; should two still run, the cheaper one wins.
            ->sortByDesc('sale_price')
            ->keyBy('product_variant_id');
    }

    /**
     * Units the customer already holds at each offer's price (expired and cancelled orders excluded).
     *
     * @param  array<int, int>  $offerIds
     * @return array<int, int>
     */
    public function purchasedBy(int $userId, array $offerIds): array
    {
        if ($offerIds === []) {
            return [];
        }

        return OrderItem::query()
            ->whereIn('flash_sale_item_id', $offerIds)
            ->whereHas('order', fn (Builder $orders) => $orders
                ->where('user_id', $userId)
                ->whereNotIn('status', OrderStatus::voidStatuses()))
            ->selectRaw('flash_sale_item_id, sum(quantity) as quantity')
            ->groupBy('flash_sale_item_id')
            ->toBase()
            ->pluck('quantity', 'flash_sale_item_id')
            ->map(fn (mixed $quantity): int => (int) $quantity)
            ->all();
    }

    /**
     * Price for a quantity of one variant, with the offer applied (or why it was not).
     *
     * @return array{unit_price: int, offer: FlashSaleItem|null, notice: string|null}
     */
    public function priceFor(ProductVariant $variant, int $quantity, int $userId): array
    {
        $offer = $this->offersFor([$variant->id])->get($variant->id);

        return self::decide($variant, $quantity, $offer, $offer ? ($this->purchasedBy($userId, [$offer->id])[$offer->id] ?? 0) : 0);
    }

    /**
     * @return array{unit_price: int, offer: FlashSaleItem|null, notice: string|null}
     */
    public static function decide(ProductVariant $variant, int $quantity, ?FlashSaleItem $offer, int $alreadyBought): array
    {
        $notice = $offer === null ? null : self::blockReason($offer, $quantity, $alreadyBought);

        return $offer !== null && $notice === null
            ? ['unit_price' => $offer->sale_price, 'offer' => $offer, 'notice' => null]
            : ['unit_price' => $variant->price, 'offer' => null, 'notice' => $notice];
    }

    /**
     * Why the sale price cannot cover this quantity, or null when it can.
     */
    public static function blockReason(FlashSaleItem $offer, int $quantity, int $alreadyBought): ?string
    {
        $remaining = $offer->remainingQuota();

        if ($remaining === 0) {
            return 'Kuota flash sale sudah habis; harga normal berlaku.';
        }

        if ($remaining !== null && $quantity > $remaining) {
            return "Harga flash sale tinggal untuk {$remaining} barang. Kurangi jumlahnya untuk mendapatkan harga itu.";
        }

        if ($offer->per_user_limit !== null && $quantity + $alreadyBought > $offer->per_user_limit) {
            $allowed = max(0, $offer->per_user_limit - $alreadyBought);

            return $allowed === 0
                ? "Jatah flash sale kamu untuk barang ini sudah terpakai (maks. {$offer->per_user_limit} per pelanggan); harga normal berlaku."
                : "Harga flash sale maks. {$offer->per_user_limit} per pelanggan. Kurangi jumlahnya jadi {$allowed} untuk mendapatkan harga itu.";
        }

        return null;
    }
}
