<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ProductVariant|null $variant */
        $variant = $this->route('variant');

        $rules = [
            'sku' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('product_variants', 'sku')->ignore($variant)],
            'option1_name' => ['nullable', 'required_with:option1_value', 'string', 'max:50'],
            'option1_value' => ['nullable', 'required_with:option1_name', 'string', 'max:50'],
            'option2_name' => ['nullable', 'required_with:option2_value', 'string', 'max:50', 'different:option1_name'],
            'option2_value' => ['nullable', 'required_with:option2_name', 'string', 'max:50'],
            'price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price', 'max:1000000000'],
            'weight_gram' => ['required', 'integer', 'min:1', 'max:100000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
        ];

        if ($variant === null) {
            $rules['stock'] = ['required', 'integer', 'min:0', 'max:100000'];
        }

        return $rules;
    }

    /**
     * Variant attributes built from the two option name/value pairs, e.g. {"Ukuran": "L"}.
     *
     * @return array<string, string>|null
     */
    public function variantAttributes(): ?array
    {
        $attributes = [];

        foreach ([1, 2] as $index) {
            $name = trim((string) $this->input("option{$index}_name"));
            $value = trim((string) $this->input("option{$index}_value"));

            if ($name !== '' && $value !== '') {
                $attributes[$name] = $value;
            }
        }

        return $attributes === [] ? null : $attributes;
    }

    /**
     * @return array{sku: string, attributes: array<string, string>|null, price: int, compare_at_price: int|null, weight_gram: int, low_stock_threshold: int}
     */
    public function variantData(): array
    {
        return [
            'sku' => $this->validated('sku'),
            'attributes' => $this->variantAttributes(),
            'price' => (int) $this->validated('price'),
            'compare_at_price' => $this->validated('compare_at_price') !== null ? (int) $this->validated('compare_at_price') : null,
            'weight_gram' => (int) $this->validated('weight_gram'),
            'low_stock_threshold' => (int) $this->validated('low_stock_threshold'),
        ];
    }
}
