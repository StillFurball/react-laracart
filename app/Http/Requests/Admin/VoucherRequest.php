<?php

namespace App\Http\Requests\Admin;

use App\Enums\VoucherType;
use App\Models\Voucher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique(Voucher::class)->ignore($this->route('voucher'))],
            'type' => ['required', Rule::enum(VoucherType::class)],
            'value' => ['required', 'integer', 'min:1', $this->input('type') === VoucherType::Percent->value ? 'max:100' : 'max:1000000000'],
            'max_discount' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'min_purchase' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }
}
