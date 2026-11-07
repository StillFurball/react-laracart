<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_variant_id
 * @property string $product_name
 * @property string|null $variant_name
 * @property string $sku
 * @property int $unit_price
 * @property int $quantity
 * @property int $weight_gram
 * @property int $line_total
 */
#[Fillable(['product_variant_id', 'product_name', 'variant_name', 'sku', 'unit_price', 'quantity', 'weight_gram', 'line_total'])]
class OrderItem extends Model
{
    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id')->withTrashed();
    }
}
