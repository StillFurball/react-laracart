<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property array<string, string>|null $attributes
 * @property int $price
 * @property int|null $compare_at_price
 * @property int $stock
 * @property int $low_stock_threshold
 * @property int $weight_gram
 * @property-read Product $product
 */
#[Fillable(['sku', 'attributes', 'price', 'compare_at_price', 'low_stock_threshold', 'weight_gram'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Human readable variant name, e.g. "L / Hitam"; null for a default variant.
     */
    public function label(): ?string
    {
        $values = array_filter(array_values($this->getAttribute('attributes') ?? []));

        return $values === [] ? null : implode(' / ', $values);
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->low_stock_threshold;
    }

    /**
     * Whether the variant can currently be bought.
     */
    public function isPurchasable(): bool
    {
        return ! $this->trashed()
            && ! $this->product->trashed()
            && $this->product->status === ProductStatus::Active;
    }
}
