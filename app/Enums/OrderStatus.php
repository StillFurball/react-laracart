<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case ReturnRequested = 'return_requested';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Dibayar',
            self::Processing => 'Diproses',
            self::Shipped => 'Dikirim',
            self::Delivered => 'Diterima',
            self::Completed => 'Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::ReturnRequested => 'Pengajuan Retur',
            self::Refunded => 'Dana Dikembalikan',
        };
    }

    /**
     * Transitions enabled in phase 1; return/refund transitions stay in the backlog.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Paid, self::Expired, self::Cancelled],
            self::Paid => [self::Processing],
            self::Processing => [self::Shipped],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Completed],
            default => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Transitions an admin may trigger manually. Expiry is system-only; so is payment
     * while the simulator is on, otherwise the admin confirms manual transfers.
     *
     * @return list<self>
     */
    public function adminTransitions(): array
    {
        $systemOnly = config('shop.payment_simulator') ? [self::Paid, self::Expired] : [self::Expired];

        return array_values(array_filter(
            $this->allowedTransitions(),
            fn (self $status): bool => ! in_array($status, $systemOnly, true),
        ));
    }

    public function releasesStock(): bool
    {
        return in_array($this, [self::Expired, self::Cancelled], true);
    }

    /**
     * Statuses of orders that have been paid; these count as sales.
     *
     * @return list<self>
     */
    public static function paidStatuses(): array
    {
        return [self::Paid, self::Processing, self::Shipped, self::Delivered, self::Completed];
    }

    /**
     * Statuses whose orders no longer consume voucher or flash sale quota.
     *
     * @return list<self>
     */
    public static function voidStatuses(): array
    {
        return [self::Expired, self::Cancelled];
    }

    /**
     * Statuses of orders that still need the customer: awaiting payment, on their way or under return.
     *
     * @return list<self>
     */
    public static function openStatuses(): array
    {
        return [self::PendingPayment, self::Paid, self::Processing, self::Shipped, self::Delivered, self::ReturnRequested];
    }
}
