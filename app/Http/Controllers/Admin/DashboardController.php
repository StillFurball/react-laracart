<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Sales summary, sales report for a date range, order counts per status, best sellers and low stock.
     *
     * Sales are paid orders, dated by when the order was placed.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $paidOrders = fn (): Builder => Order::whereIn('status', OrderStatus::paidStatuses());
        $paidInRange = fn (): Builder => $paidOrders()->whereBetween('created_at', [$from, $to]);

        $totals = $paidInRange()
            ->selectRaw('count(*) as orders, coalesce(sum(subtotal), 0) as gross, coalesce(sum(discount_total), 0) as discount, coalesce(sum(shipping_cost), 0) as shipping')
            ->toBase()
            ->first();

        $statusCounts = Order::selectRaw('status, count(*) as total')->groupBy('status')->toBase()->pluck('total', 'status');

        return Inertia::render('admin/dashboard', [
            'sales' => [
                'today' => (int) $paidOrders()->where('created_at', '>=', now()->startOfDay())->sum('grand_total'),
                'week' => (int) $paidOrders()->where('created_at', '>=', now()->startOfWeek())->sum('grand_total'),
                'month' => (int) $paidOrders()->where('created_at', '>=', now()->startOfMonth())->sum('grand_total'),
            ],
            'report' => [
                'orders' => (int) $totals->orders,
                'gross' => (int) $totals->gross,
                'discount' => (int) $totals->discount,
                'shipping' => (int) $totals->shipping,
                'net' => (int) $totals->gross - (int) $totals->discount,
                'daily' => $paidInRange()
                    ->selectRaw('date(created_at) as day, count(*) as orders, sum(subtotal - discount_total) as net')
                    ->groupBy('day')
                    ->orderBy('day')
                    ->toBase()
                    ->get()
                    ->map(fn (object $row): array => ['day' => $row->day, 'orders' => (int) $row->orders, 'net' => (int) $row->net]),
            ],
            'statusCounts' => collect(OrderStatus::cases())
                ->reject(fn (OrderStatus $status): bool => in_array($status, [OrderStatus::ReturnRequested, OrderStatus::Refunded], true))
                ->map(fn (OrderStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'total' => (int) ($statusCounts[$status->value] ?? 0),
                ])
                ->values(),
            'topProducts' => OrderItem::whereIn('order_id', $paidInRange()->select('id'))
                ->selectRaw('product_name, sum(quantity) as quantity, sum(line_total) as revenue')
                ->groupBy('product_name')
                ->orderByDesc('quantity')
                ->limit(5)
                ->toBase()
                ->get()
                ->map(fn (object $row): array => ['name' => $row->product_name, 'quantity' => (int) $row->quantity, 'revenue' => (int) $row->revenue]),
            'lowStock' => ProductVariant::with('product:id,name')
                ->whereHas('product')
                ->whereColumn('stock', '<=', 'low_stock_threshold')
                ->orderBy('stock')
                ->limit(5)
                ->get()
                ->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name,
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                ]),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
