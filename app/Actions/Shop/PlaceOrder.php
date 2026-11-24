<?php

namespace App\Actions\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Cart;
use App\Models\FlashSaleItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns the customer's cart into a pending order: revalidates prices (flash sale prices included),
 * stock and voucher, reserves stock and flash sale quota atomically and snapshots everything in one
 * transaction.
 */
class PlaceOrder
{
    public function __construct(
        private SummarizeCart $summarizeCart,
        private AdjustStock $adjustStock,
    ) {}

    /**
     * @param  array{shipping_service: string, payment_method: string, voucher_code?: string|null, customer_note?: string|null, idempotency_key: string, expected_total: int|string}  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, array $data): Order
    {
        $existing = $this->findExisting($user, $data['idempotency_key']);

        if ($existing !== null) {
            return $existing;
        }

        $address = $user->address ?? throw ValidationException::withMessages([
            'address' => 'Isi alamat pengiriman terlebih dahulu.',
        ]);

        // Looked up before the transaction: a failed guess must still count when the order rolls back.
        $voucherId = null;

        if (filled($data['voucher_code'] ?? null)) {
            ['voucher' => $voucher, 'error' => $error] = $this->summarizeCart->findVoucher($user, (string) $data['voucher_code']);

            $voucherId = $voucher->id ?? throw ValidationException::withMessages(['voucher_code' => $error]);
        }

        try {
            return DB::transaction(function () use ($user, $data, $address, $voucherId): Order {
                // Lock first: checkouts of one customer run one at a time, so two tabs cannot order the
                // same cart twice, and every read below sees the orders a concurrent checkout committed
                // (per-customer voucher and flash sale limits depend on that).
                $cart = Cart::whereBelongsTo($user)->lockForUpdate()->first();
                $summary = $this->summarizeCart->handle($cart);

                if ($summary['items']->isEmpty()) {
                    throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
                }

                if ($summary['issues'] !== []) {
                    throw ValidationException::withMessages(['cart' => $summary['issues'][0]]);
                }

                $voucher = null;
                $discount = 0;

                if ($voucherId !== null) {
                    $voucher = Voucher::whereKey($voucherId)->lockForUpdate()->first();
                    $reason = $voucher === null
                        ? 'Kode voucher tidak ditemukan.'
                        : $voucher->rejectionReason($user, $summary['subtotal']);

                    if ($reason !== null) {
                        throw ValidationException::withMessages(['voucher_code' => $reason]);
                    }

                    $discount = $voucher->discountFor($summary['subtotal']);
                }

                $service = SummarizeCart::shippingServices()[$data['shipping_service']];
                $shippingCost = SummarizeCart::shippingCost($data['shipping_service'], $summary['weight_gram']);
                $grandTotal = $summary['subtotal'] - $discount + $shippingCost;

                if ((int) $data['expected_total'] !== $grandTotal) {
                    throw ValidationException::withMessages([
                        'expected_total' => 'Total pesanan berubah. Periksa kembali ringkasan sebelum melanjutkan.',
                    ]);
                }

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $user->id,
                    'status' => OrderStatus::PendingPayment,
                    'subtotal' => $summary['subtotal'],
                    'discount_total' => $discount,
                    'shipping_cost' => $shippingCost,
                    'tax_total' => 0,
                    'grand_total' => $grandTotal,
                    'voucher_id' => $voucher?->id,
                    'voucher_code' => $voucher?->code,
                    'shipping_address' => $address->toSnapshot(),
                    'courier' => config('shop.shipping.courier'),
                    'courier_service' => $service['name'],
                    'payment_method' => PaymentMethod::from($data['payment_method']),
                    'payment_due_at' => now()->addHours((int) config('shop.payment_due_hours')),
                    'customer_note' => $data['customer_note'] ?? null,
                    'idempotency_key' => $data['idempotency_key'],
                ]);

                foreach ($summary['items'] as $item) {
                    $variant = $item->variant;
                    $offer = $summary['offers'][$item->id] ?? null;

                    $this->adjustStock->handle($variant, -$item->quantity, StockMovementType::Reserve, $user, $order);

                    if ($offer !== null) {
                        $this->reserveFlashSaleQuota($offer, $item->quantity, $variant->product->name);
                    }

                    $order->items()->create([
                        'product_variant_id' => $variant->id,
                        'flash_sale_item_id' => $offer?->id,
                        'product_name' => $variant->product->name,
                        'variant_name' => $variant->label(),
                        'sku' => $variant->sku,
                        'unit_price' => (int) $item->getAttribute('unit_price'),
                        'regular_unit_price' => $offer !== null ? $variant->price : null,
                        'quantity' => $item->quantity,
                        'weight_gram' => $variant->weight_gram,
                        'line_total' => (int) $item->getAttribute('line_total'),
                    ]);
                }

                $order->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => OrderStatus::PendingPayment,
                    'user_id' => $user->id,
                    'note' => 'Pesanan dibuat.',
                ]);

                $cart->items()->delete();

                DB::afterCommit(fn () => $user->notify(new OrderStatusUpdated($order)));

                return $order;
            });
        } catch (UniqueConstraintViolationException $exception) {
            return $this->findExisting($user, $data['idempotency_key']) ?? throw $exception;
        }
    }

    /**
     * Count units against the offer's quota in one conditional update, so concurrent checkouts can
     * never sell past it; the loser gets the normal price on the next try.
     *
     * @throws ValidationException when the quota ran out since the cart was priced
     */
    private function reserveFlashSaleQuota(FlashSaleItem $offer, int $quantity, string $productName): void
    {
        $reserved = FlashSaleItem::whereKey($offer->id)
            ->where(fn (Builder $query) => $query
                ->whereNull('quota')
                ->orWhereRaw('sold_count + ? <= quota', [$quantity]))
            ->increment('sold_count', $quantity);

        if ($reserved === 0) {
            throw ValidationException::withMessages([
                'cart' => "Kuota flash sale {$productName} baru saja habis. Periksa kembali total pesanan.",
            ]);
        }
    }

    private function findExisting(User $user, string $idempotencyKey): ?Order
    {
        return Order::where('user_id', $user->id)->where('idempotency_key', $idempotencyKey)->first();
    }
}
