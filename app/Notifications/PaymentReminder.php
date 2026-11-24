<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminder extends Notification implements ShouldQueue
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

    public function toMail(User $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject("Segera bayar pesanan {$order->order_number}")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pesanan {$order->order_number} belum dibayar dan akan kedaluwarsa pada ".$order->payment_due_at->translatedFormat('d M Y H:i').'.')
            ->line('Setelah kedaluwarsa, stok dilepas dan pesanan tidak dapat dibayar lagi.')
            ->action('Bayar sekarang', route('orders.show', $order));
    }
}
