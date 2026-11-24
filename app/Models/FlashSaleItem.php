<?php

namespace App\Models;

use Database\Factories\FlashSaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A variant offered in a flash sale. sold_count is not fillable: only checkout (+) and an expired or
 * cancelled order (−) change it, atomically.
 *
 * @property int $id
 * @property int $flash_sale_id
 * @property int $product_variant_id
 * @property int $sale_price
 * @property int|null $quota
 * @property int $sold_count
 * @property int|null $per_user_limit
 * @property-read FlashSale $flashSale
 * @property-read ProductVariant $variant
 */
#[Fillable(['product_variant_id', 'sale_price', 'quota', 'per_user_limit'])]
class FlashSaleItem extends Model
{
    /** @use HasFactory<FlashSaleItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<FlashSale, $this>
     */
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Units still available at the sale price; null when the sale has no quota (stock is the only cap).
     */
    public function remainingQuota(): ?int
    {
        return $this->quota === null ? null : max(0, $this->quota - $this->sold_count);
    }

    /**
     * Whole-number discount against a regular price, e.g. 25 for 100.000 → 75.000.
     */
    public function discountPercent(int $regularPrice): int
    {
        return $regularPrice > 0 ? (int) round((1 - $this->sale_price / $regularPrice) * 100) : 0;
    }
}
