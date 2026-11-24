<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Customer email for the order milestones: placed (pending payment), paid and shipped.
 */
class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Statuses that trigger this email.
     *
     * @var list<OrderStatus>
     */
    public const STATUSES = [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Shipped];

    /**
     * Captured at dispatch: the order may move on before the queued email is sent.
     */
    public OrderStatus $status;

    public function __construct(public Order $order)
    {
        $this->status = $order->status;
    }

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
        $total = 'Rp'.number_format($order->grand_total, 0, ',', '.');

        $message = (new MailMessage)->greeting("Halo, {$notifiable->name}!");

        $message = match ($this->status) {
            OrderStatus::PendingPayment => $message
                ->subject("Pesanan {$order->order_number} berhasil dibuat")
                ->line("Pesanan {$order->order_number} sebesar {$total} menunggu pembayaran.")
                ->line('Selesaikan pembayaran sebelum '.$order->payment_due_at->translatedFormat('d M Y H:i').'.'),
            OrderStatus::Paid => $message
                ->subject("Pembayaran pesanan {$order->order_number} diterima")
                ->line("Pembayaran {$total} untuk pesanan {$order->order_number} sudah kami terima. Pesanan akan segera diproses."),
            default => $message
                ->subject("Pesanan {$order->order_number} sedang dikirim")
                ->line("Pesanan {$order->order_number} sudah dikirim via {$order->courier} {$order->courier_service}.")
                ->line('Nomor resi: '.$order->shipment?->tracking_number),
        };

        return $message->action('Lihat pesanan', route('orders.show', $order));
    }
}
