<?php

namespace App\Enums;

enum VoucherType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
}
