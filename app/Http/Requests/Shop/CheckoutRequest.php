<?php

namespace App\Http\Requests\Shop;

use App\Actions\Shop\SummarizeCart;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_service' => ['required', 'string', Rule::in(array_keys(SummarizeCart::shippingServices()))],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->only(PaymentMethod::available())],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
            'idempotency_key' => ['required', 'uuid'],
            'expected_total' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * The validated checkout input, typed for PlaceOrder.
     *
     * @return array{shipping_service: string, payment_method: string, voucher_code: string|null, customer_note: string|null, idempotency_key: string, expected_total: int}
     */
    public function orderData(): array
    {
        return [
            'shipping_service' => $this->string('shipping_service')->toString(),
            'payment_method' => $this->string('payment_method')->toString(),
            'voucher_code' => $this->filled('voucher_code') ? $this->string('voucher_code')->toString() : null,
            'customer_note' => $this->filled('customer_note') ? $this->string('customer_note')->toString() : null,
            'idempotency_key' => $this->string('idempotency_key')->toString(),
            'expected_total' => $this->integer('expected_total'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Setujui Syarat & Ketentuan sebelum membuat pesanan.',
            'shipping_service.*' => 'Pilih layanan pengiriman.',
            'payment_method.*' => 'Pilih metode pembayaran.',
        ];
    }
}
