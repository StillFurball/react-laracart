<?php

namespace App\Actions\Shop;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewPaidOrder;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Applies a status transition with its side effects: history, shipment data, stock release and emails.
 */
class ChangeOrderStatus
{
    public function __construct(private AdjustStock $adjustStock) {}

    /**
     * @throws ValidationException when the transition is not allowed
     */
    public function handle(
        Order $order,
        OrderStatus $to,
        ?User $actor = null,
        ?string $note = null,
        ?string $trackingNumber = null,
    ): Order {
        return DB::transaction(function () use ($order, $to, $actor, $note, $trackingNumber): Order {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $order->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Status tidak dapat diubah dari \"{$from->label()}\" ke \"{$to->label()}\".",
                ]);
            }

            if ($to === OrderStatus::Shipped) {
                if (blank($trackingNumber)) {
                    throw ValidationException::withMessages(['tracking_number' => 'Nomor resi wajib diisi.']);
                }

                $order->shipment()->create([
                    'courier' => $order->courier,
                    'service' => $order->courier_service,
                    'tracking_number' => trim($trackingNumber),
                    'shipped_at' => now(),
                ]);
            }

            if ($to === OrderStatus::Delivered) {
                $order->shipment?->update(['delivered_at' => now()]);
            }

            if ($to->releasesStock()) {
                foreach ($order->items()->with('variant')->get() as $item) {
                    $this->adjustStock->handle($item->variant, $item->quantity, StockMovementType::Release, $actor, $order);
                }
            }

            $order->update(['status' => $to]);

            $order->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor?->id,
                'note' => $note,
            ]);

            if (in_array($to, OrderStatusUpdated::STATUSES, true)) {
                DB::afterCommit(fn () => $order->user->notify(new OrderStatusUpdated($order)));
            }

            if ($to === OrderStatus::Paid) {
                DB::afterCommit(fn () => Notification::send(User::where('role', UserRole::Admin)->get(), new NewPaidOrder($order)));
            }

            return $order;
        });
    }
}
