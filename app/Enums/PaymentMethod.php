<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Qris = 'qris';

    public function label(): string
    {
        $label = match ($this) {
            self::BankTransfer => 'Transfer bank',
            self::Qris => 'QRIS',
        };

        return config('shop.payment_simulator') ? "{$label} (simulasi)" : $label;
    }

    /**
     * Methods offered at checkout; QRIS has no manual flow, so only the simulator offers it.
     *
     * @return list<self>
     */
    public static function available(): array
    {
        return config('shop.payment_simulator') ? self::cases() : [self::BankTransfer];
    }
}
