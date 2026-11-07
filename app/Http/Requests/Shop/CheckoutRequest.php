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
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'voucher_code' => ['nullable', 'string', 'max:50'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
            'idempotency_key' => ['required', 'uuid'],
            'expected_total' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'Setujui Syarat & Ketentuan demo sebelum membuat pesanan.',
            'shipping_service.*' => 'Pilih layanan pengiriman.',
            'payment_method.*' => 'Pilih metode pembayaran.',
        ];
    }
}
