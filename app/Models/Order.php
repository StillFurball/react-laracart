<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property OrderStatus $status
 * @property int $subtotal
 * @property int $discount_total
 * @property int $shipping_cost
 * @property int $tax_total
 * @property int $grand_total
 * @property int|null $voucher_id
 * @property string|null $voucher_code
 * @property array<string, string> $shipping_address
 * @property string $courier
 * @property string $courier_service
 * @property PaymentMethod $payment_method
 * @property Carbon $payment_due_at
 * @property string|null $customer_note
 * @property string|null $internal_note
 * @property string $idempotency_key
 * @property Carbon $created_at
 */
#[Fillable([
    'order_number', 'user_id', 'status', 'subtotal', 'discount_total', 'shipping_cost', 'tax_total',
    'grand_total', 'voucher_id', 'voucher_code', 'shipping_address', 'courier', 'courier_service',
    'payment_method', 'payment_due_at', 'customer_note', 'internal_note', 'idempotency_key',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'shipping_address' => 'array',
            'payment_due_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    public function isPaymentOverdue(): bool
    {
        return $this->payment_due_at->isPast();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Voucher, $this>
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    /**
     * @return HasOne<Shipment, $this>
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }
}
