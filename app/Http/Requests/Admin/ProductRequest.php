<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates product data; on create it also validates the first (default) variant.
 */
class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique(Product::class)->ignore($product)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'weight_gram' => ['required', 'integer', 'min:1', 'max:100000'],
        ];

        if ($product === null) {
            $rules += [
                'sku' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('product_variants', 'sku')],
                'price' => ['required', 'integer', 'min:1', 'max:1000000000'],
                'compare_at_price' => ['nullable', 'integer', 'gt:price', 'max:1000000000'],
                'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            ];
        }

        return $rules;
    }
}
