<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Reserve = 'reserve';
    case Release = 'release';
    case Adjustment = 'adjustment';
}
