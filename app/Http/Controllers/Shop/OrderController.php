<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * The customer's order history.
     */
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->withCount('items')
            ->latest('id')
            ->paginate(10)
            ->through(fn (Order $order): array => [
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'grand_total' => $order->grand_total,
                'items_count' => $order->items_count,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        return Inertia::render('account/orders/index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Order detail with status timeline and the payment simulator while unpaid.
     */
    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        return Inertia::render('account/orders/show', [
            'order' => self::detail($order),
        ]);
    }

    /**
     * Cancel an unpaid order and release its stock.
     */
    public function cancel(Request $request, Order $order, ChangeOrderStatus $changeOrderStatus): RedirectResponse
    {
        Gate::authorize('view', $order);

        $changeOrderStatus->handle($order, OrderStatus::Cancelled, $request->user(), 'Dibatalkan pelanggan.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesanan dibatalkan dan stok dikembalikan.']);

        return to_route('orders.show', $order);
    }

    /**
     * Confirm that a delivered order has been received.
     */
    public function complete(Request $request, Order $order, ChangeOrderStatus $changeOrderStatus): RedirectResponse
    {
        Gate::authorize('view', $order);

        $changeOrderStatus->handle($order, OrderStatus::Completed, $request->user(), 'Dikonfirmasi diterima oleh pelanggan.');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Terima kasih! Pesanan selesai.']);

        return to_route('orders.show', $order);
    }

    /**
     * Order data shared by the customer and admin detail pages.
     *
     * @return array<string, mixed>
     */
    public static function detail(Order $order): array
    {
        $order->load(['items', 'payments', 'shipment', 'statusHistories.user']);

        return [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'subtotal' => $order->subtotal,
            'discount_total' => $order->discount_total,
            'shipping_cost' => $order->shipping_cost,
            'tax_total' => $order->tax_total,
            'grand_total' => $order->grand_total,
            'voucher_code' => $order->voucher_code,
            'shipping_address' => $order->shipping_address,
            'courier' => $order->courier,
            'courier_service' => $order->courier_service,
            'payment_method' => $order->payment_method->value,
            'payment_method_label' => $order->payment_method->label(),
            'payment_due_at' => $order->payment_due_at->toIso8601String(),
            'customer_note' => $order->customer_note,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map->only(['id', 'product_name', 'variant_name', 'sku', 'unit_price', 'quantity', 'line_total']),
            'payments' => $order->payments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->gateway_transaction_id,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'created_at' => $payment->created_at->toIso8601String(),
            ]),
            'shipment' => $order->shipment?->only(['courier', 'service', 'tracking_number', 'shipped_at', 'delivered_at']),
            'history' => $order->statusHistories->map(fn (OrderStatusHistory $history): array => [
                'id' => $history->id,
                'status_label' => $history->to_status->label(),
                'note' => $history->note,
                'actor' => $history->user?->name ?? 'Sistem',
                'created_at' => $history->created_at->toIso8601String(),
            ]),
        ];
    }
}
