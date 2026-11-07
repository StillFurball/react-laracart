<?php

namespace App\Actions\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Local payment simulator. The browser only picks an outcome; amount and status come from the server.
 */
class SimulatePayment
{
    public const OUTCOMES = ['success', 'failed', 'expired'];

    public function __construct(private ChangeOrderStatus $changeOrderStatus) {}

    /**
     * @param  'success'|'failed'|'expired'  $outcome
     * @return array{type: 'success'|'info'|'warning'|'error', message: string}
     */
    public function handle(Order $order, string $outcome): array
    {
        return DB::transaction(function () use ($order, $outcome): array {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== OrderStatus::PendingPayment) {
                return ['type' => 'info', 'message' => "Pesanan berstatus \"{$order->status->label()}\"; tidak ada perubahan."];
            }

            if ($outcome === 'expired' || $order->isPaymentOverdue()) {
                $this->changeOrderStatus->handle($order, OrderStatus::Expired, null, 'Batas waktu pembayaran habis.');

                return ['type' => 'warning', 'message' => 'Pesanan kedaluwarsa dan stok dikembalikan.'];
            }

            $paid = $outcome === 'success';

            $order->payments()->create([
                'gateway' => 'simulator',
                'gateway_transaction_id' => 'SIM-'.Str::upper((string) Str::ulid()),
                'method' => $order->payment_method,
                'amount' => $order->grand_total,
                'status' => $paid ? PaymentStatus::Paid : PaymentStatus::Failed,
                'paid_at' => $paid ? now() : null,
            ]);

            if (! $paid) {
                return ['type' => 'error', 'message' => 'Pembayaran simulasi gagal. Kamu dapat mencoba lagi sebelum batas waktu.'];
            }

            $this->changeOrderStatus->handle($order, OrderStatus::Paid, null, 'Pembayaran simulasi berhasil.');

            return ['type' => 'success', 'message' => 'Pembayaran berhasil. Pesanan akan segera diproses.'];
        });
    }
}
