<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\VoucherType;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property VoucherType $type
 * @property int $value
 * @property int|null $max_discount
 * @property int $min_purchase
 * @property int|null $quota
 * @property int|null $per_user_limit
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
#[Fillable(['code', 'type', 'value', 'max_discount', 'min_purchase', 'quota', 'per_user_limit', 'starts_at', 'ends_at', 'is_active'])]
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Usage counts every order except expired/cancelled ones, so quota frees itself automatically.
     */
    public function usageCount(?User $user = null): int
    {
        return $this->orders()
            ->whereNotIn('status', OrderStatus::voidStatuses())
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->count();
    }

    /**
     * Reason the voucher cannot be applied, or null when it is valid.
     */
    public function rejectionReason(User $user, int $subtotal): ?string
    {
        if (! $this->is_active
            || ($this->starts_at !== null && $this->starts_at->isFuture())
            || ($this->ends_at !== null && $this->ends_at->isPast())) {
            return 'Voucher tidak aktif atau sudah tidak berlaku.';
        }

        if ($subtotal < $this->min_purchase) {
            return sprintf(
                'Kurang %s untuk memakai voucher ini (minimum belanja %s).',
                self::rupiah($this->min_purchase - $subtotal),
                self::rupiah($this->min_purchase),
            );
        }

        if ($this->quota !== null && $this->usageCount() >= $this->quota) {
            return 'Kuota voucher sudah habis.';
        }

        if ($this->per_user_limit !== null && $this->usageCount($user) >= $this->per_user_limit) {
            return 'Kamu sudah mencapai batas pemakaian voucher ini.';
        }

        return null;
    }

    /**
     * Discount for the given subtotal (shipping is never discounted).
     */
    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            VoucherType::Percent => intdiv($subtotal * $this->value, 100),
            VoucherType::Fixed => $this->value,
        };

        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
