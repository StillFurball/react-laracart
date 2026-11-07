<?php

namespace App\Actions\Shop;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The only place that changes variant stock; every change writes a ledger row.
 */
class AdjustStock
{
    /**
     * @throws ValidationException when a decrease would make stock negative
     */
    public function handle(
        ProductVariant $variant,
        int $quantity,
        StockMovementType $type,
        ?User $user = null,
        ?Order $order = null,
        ?string $note = null,
    ): StockMovement {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Stock quantity change cannot be zero.');
        }

        return DB::transaction(function () use ($variant, $quantity, $type, $user, $order, $note): StockMovement {
            if ($quantity < 0) {
                $affected = ProductVariant::withTrashed()
                    ->whereKey($variant->id)
                    ->where('stock', '>=', -$quantity)
                    ->decrement('stock', -$quantity);

                if ($affected === 0) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok {$variant->sku} tidak mencukupi.",
                    ]);
                }
            } else {
                ProductVariant::withTrashed()->whereKey($variant->id)->increment('stock', $quantity);
            }

            $stockAfter = (int) ProductVariant::withTrashed()->whereKey($variant->id)->value('stock');

            $variant->stock = $stockAfter;
            $variant->syncOriginalAttribute('stock');

            return StockMovement::create([
                'product_variant_id' => $variant->id,
                'type' => $type,
                'quantity' => $quantity,
                'stock_after' => $stockAfter,
                'order_id' => $order?->id,
                'user_id' => $user?->id,
                'note' => $note,
            ]);
        });
    }
}
