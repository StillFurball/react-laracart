<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Shop\PlaceOrder;
use App\Actions\Shop\SummarizeCart;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Single-page checkout; shipping service and voucher are previewed via query string.
     */
    public function show(Request $request, SummarizeCart $summarizeCart): Response|RedirectResponse
    {
        $user = $request->user();
        $cart = $user->cart;
        $summary = $summarizeCart->handle($cart);

        if ($summary['items']->isEmpty() || $summary['issues'] !== []) {
            return to_route('cart.index');
        }

        if ($user->address === null) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Isi alamat pengiriman sebelum checkout.']);

            return to_route('address.edit');
        }

        $services = SummarizeCart::shippingServices();
        $service = array_key_exists((string) $request->query('shipping_service'), $services)
            ? (string) $request->query('shipping_service')
            : array_key_first($services);
        $voucherCode = Str::limit(trim((string) $request->query('voucher_code')), 50, '');

        $totals = $summarizeCart->totals($user, $summary['subtotal'], $summary['weight_gram'], $service, $voucherCode);
        $summarizeCart->acknowledgePriceChanges($cart);

        return Inertia::render('shop/checkout', [
            'items' => $summary['items']->map(fn ($item) => SummarizeCart::itemProps($item)),
            'address' => $user->address->toSnapshot(),
            'services' => collect($services)->map(fn (array $config, string $key): array => [
                'key' => $key,
                'name' => $config['name'],
                'eta' => $config['eta'],
                'cost' => SummarizeCart::shippingCost($key, $summary['weight_gram']),
            ])->values(),
            'courier' => config('shop.shipping.courier'),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $method): array => [
                'value' => $method->value,
                'label' => $method->label(),
            ]),
            'selection' => [
                'shipping_service' => $service,
                'voucher_code' => $voucherCode,
            ],
            'voucherError' => $totals['voucher_error'],
            'appliedVoucher' => $totals['voucher']?->code,
            'totals' => [
                'subtotal' => $summary['subtotal'],
                'discount' => $totals['discount'],
                'shipping_cost' => $totals['shipping_cost'],
                'tax_total' => 0,
                'grand_total' => $totals['grand_total'],
            ],
            'weightGram' => $summary['weight_gram'],
            'priceChanges' => $summary['price_changes'],
            'idempotencyKey' => (string) Str::uuid(),
            'paymentDueHours' => (int) config('shop.payment_due_hours'),
        ]);
    }

    /**
     * Place the order (idempotent per checkout page render).
     */
    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $order = $placeOrder->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Pesanan {$order->order_number} dibuat. Selesaikan pembayaran simulasi."]);

        return to_route('orders.show', $order);
    }
}
