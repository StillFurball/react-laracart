<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin email: a variant's stock just dropped to or below its threshold.
 */
class LowStock extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductVariant $variant) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $variant = $this->variant;
        $state = $variant->stock === 0 ? 'habis' : "tersisa {$variant->stock}";

        return (new MailMessage)
            ->subject("Stok menipis: {$variant->sku}")
            ->line("Stok {$variant->product->name} ({$variant->sku}) {$state}; ambang batas {$variant->low_stock_threshold}.")
            ->action('Lihat stok', route('admin.stock.index', ['sku' => $variant->sku]));
    }
}
