<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\SimulatePayment;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class OrderPaymentController extends Controller
{
    /**
     * Run the local payment simulator for an order (demo mode only).
     */
    public function store(Request $request, Order $order, SimulatePayment $simulatePayment): RedirectResponse
    {
        Gate::authorize('view', $order);

        $validated = $request->validate([
            'outcome' => ['required', Rule::in(SimulatePayment::OUTCOMES)],
        ]);

        Inertia::flash('toast', $simulatePayment->handle($order, $validated['outcome']));

        return to_route('orders.show', $order);
    }
}
