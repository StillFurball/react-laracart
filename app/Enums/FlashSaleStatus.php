<?php

namespace App\Enums;

/**
 * Where a flash sale stands right now; derived from its schedule and active flag, never stored.
 */
enum FlashSaleStatus: string
{
    case Running = 'running';
    case Scheduled = 'scheduled';
    case Ended = 'ended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Running => 'Berjalan',
            self::Scheduled => 'Terjadwal',
            self::Ended => 'Selesai',
            self::Inactive => 'Nonaktif',
        };
    }
}
