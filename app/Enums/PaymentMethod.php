<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Qris = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transfer bank (simulasi)',
            self::Qris => 'QRIS (simulasi)',
        };
    }
}
