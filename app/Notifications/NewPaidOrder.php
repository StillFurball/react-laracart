<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin email: a paid order is waiting to be processed.
 */
class NewPaidOrder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject("Pesanan baru dibayar: {$order->order_number}")
            ->line("Pesanan {$order->order_number} senilai Rp".number_format($order->grand_total, 0, ',', '.').' sudah dibayar dan siap diproses.')
            ->action('Proses pesanan', route('admin.orders.show', $order));
    }
}
