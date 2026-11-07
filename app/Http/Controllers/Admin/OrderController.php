<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Shop\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Shop\OrderController as ShopOrderController;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Order list with search (order number / email) and status filter.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $search = $filters['q'] ?? null;

        $orders = Order::with('user:id,name,email')
            ->when($search, fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $term = '%'.addcslashes($search, '%_\\').'%';

                $query->where('order_number', 'like', $term)
                    ->orWhereHas('user', fn (Builder $users) => $users->where('email', 'like', $term));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'order_number' => $order->order_number,
                'customer' => $order->user->only(['name', 'email']),
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => ['q' => $search ?? '', 'status' => $filters['status'] ?? ''],
            'statuses' => self::statusOptions(OrderStatus::cases()),
        ]);
    }

    /**
     * Order detail with the transitions an admin may apply.
     */
    public function show(Order $order): Response
    {
        $order->load('user:id,name,email,phone');

        return Inertia::render('admin/orders/show', [
            'order' => [
                ...ShopOrderController::detail($order),
                'internal_note' => $order->internal_note,
                'customer' => $order->user->only(['name', 'email', 'phone']),
            ],
            'transitions' => self::statusOptions($order->status->adminTransitions()),
        ]);
    }

    /**
     * Apply a manual status transition (tracking number required for "shipped").
     */
    public function updateStatus(Request $request, Order $order, ChangeOrderStatus $changeOrderStatus): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $status = OrderStatus::from($validated['status']);

        if (! in_array($status, $order->status->adminTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => "Status tidak dapat diubah dari \"{$order->status->label()}\" ke \"{$status->label()}\".",
            ]);
        }

        $changeOrderStatus->handle($order, $status, $request->user(), $validated['note'] ?? null, $validated['tracking_number'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Status pesanan menjadi \"{$status->label()}\"."]);

        return to_route('admin.orders.show', $order);
    }

    /**
     * Save the internal note (never shown to the customer).
     */
    public function updateNote(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan internal disimpan.']);

        return to_route('admin.orders.show', $order);
    }

    /**
     * @param  array<int, OrderStatus>  $statuses
     * @return list<array{value: string, label: string}>
     */
    private static function statusOptions(array $statuses): array
    {
        return array_values(array_map(fn (OrderStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], $statuses));
    }
}
