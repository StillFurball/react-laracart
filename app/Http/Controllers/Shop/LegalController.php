<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Terms of sale, filled in from the store's own settings so they match how checkout behaves.
     */
    public function terms(): Response
    {
        return Inertia::render('legal/terms', [
            'paymentDueHours' => (int) config('shop.payment_due_hours'),
            'autoCompleteDays' => (int) config('shop.auto_complete_days'),
            'maxPerItem' => (int) config('shop.max_quantity_per_item'),
            'paymentSimulator' => (bool) config('shop.payment_simulator'),
        ])->withViewData('seo', [
            'title' => 'Syarat & Ketentuan',
            'description' => 'Syarat belanja di '.config('app.name').': pemesanan, pembayaran, pengiriman, flash sale dan voucher.',
        ]);
    }

    /**
     * What personal data the store keeps, why, and how to have it erased.
     */
    public function privacy(): Response
    {
        return Inertia::render('legal/privacy')->withViewData('seo', [
            'title' => 'Kebijakan Privasi',
            'description' => 'Data apa yang disimpan '.config('app.name').', untuk apa, dan cara menghapusnya.',
        ]);
    }
}
