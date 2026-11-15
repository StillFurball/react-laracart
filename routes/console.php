<?php

use App\Actions\Shop\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentReminder;
use Illuminate\Database\Eloquent\Builder;
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

Artisan::command('orders:remind-payment', function () {
    $reminded = 0;

    Order::with('user')
        ->where('status', OrderStatus::PendingPayment)
        ->whereNull('payment_reminder_sent_at')
        ->whereBetween('payment_due_at', [now(), now()->addHours((int) config('shop.payment_reminder_hours'))])
        ->each(function (Order $order) use (&$reminded): void {
            $order->update(['payment_reminder_sent_at' => now()]);
            $order->user->notify(new PaymentReminder($order));
            $reminded++;
        });

    $this->info("{$reminded} pengingat pembayaran dikirim.");
})->purpose('Email customers whose unpaid orders are about to expire');

Artisan::command('orders:auto-complete', function (ChangeOrderStatus $changeOrderStatus) {
    $completed = 0;

    Order::where('status', OrderStatus::Delivered)
        ->whereHas('shipment', fn (Builder $query) => $query->where('delivered_at', '<=', now()->subDays((int) config('shop.auto_complete_days'))))
        ->each(function (Order $order) use ($changeOrderStatus, &$completed): void {
            try {
                $changeOrderStatus->handle($order, OrderStatus::Completed, null, 'Selesai otomatis setelah '.config('shop.auto_complete_days').' hari.');
                $completed++;
            } catch (ValidationException) {
                // Confirmed by the customer in the meantime.
            }
        });

    $this->info("{$completed} pesanan selesai otomatis.");
})->purpose('Complete delivered orders the customer has not confirmed in time');

Artisan::command('shop:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Tidak ada pengguna dengan email {$email}. Daftar dulu lewat halaman registrasi.");

        return 1;
    }

    $user->forceFill(['role' => UserRole::Admin])->save();
    $this->info("{$email} sekarang admin.");

    return 0;
})->purpose('Promote a registered user to store admin (use instead of the demo seeder in production)');

Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();
Schedule::command('orders:remind-payment')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('orders:auto-complete')->hourly()->withoutOverlapping();
