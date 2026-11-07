<?php

use App\Actions\Shop\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire', function (ChangeOrderStatus $changeOrderStatus) {
    $expired = 0;

    Order::where('status', OrderStatus::PendingPayment)
        ->where('payment_due_at', '<=', now())
        ->each(function (Order $order) use ($changeOrderStatus, &$expired): void {
            try {
                $changeOrderStatus->handle($order, OrderStatus::Expired, null, 'Batas waktu pembayaran habis.');
                $expired++;
            } catch (ValidationException) {
                // Paid or cancelled in the meantime; nothing to expire.
            }
        });

    $this->info("{$expired} pesanan kedaluwarsa.");
})->purpose('Expire unpaid orders past their payment deadline and release their stock');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
