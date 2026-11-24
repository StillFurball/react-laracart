<?php

namespace App\Http\Requests\Admin;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use App\Models\Voucher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Adds a variant to a flash sale (store) or changes an offer's price and limits (update).
 */
class FlashSaleItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'sale_price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];

        if ($this->offer() === null) {
            $rules['product_variant_id'] = [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->whereNull('deleted_at'),
                Rule::unique('flash_sale_items')->where('flash_sale_id', $this->flashSale()->id),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_variant_id.unique' => 'Varian ini sudah ada di flash sale ini.',
            'product_variant_id.required' => 'Pilih varian yang dijual.',
        ];
    }

    /**
     * The sale price must undercut the normal price, a quota cannot drop below what is already sold,
     * and a variant cannot be offered by two sales whose schedules overlap.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $offer = $this->offer();
                $variant = $offer !== null
                    ? $offer->variant
                    : ProductVariant::findOrFail($this->integer('product_variant_id'));

                if ($this->integer('sale_price') >= $variant->price) {
                    $validator->errors()->add('sale_price', 'Harga flash sale harus di bawah harga normal ('.Voucher::rupiah($variant->price).').');
                }

                if ($offer !== null && $this->filled('quota') && $this->integer('quota') < $offer->sold_count) {
                    $validator->errors()->add('quota', "Kuota tidak boleh di bawah jumlah yang sudah terjual ({$offer->sold_count}).");
                }

                if ($offer === null) {
                    $flashSale = $this->flashSale();
                    $clash = FlashSaleItem::query()
                        ->where('product_variant_id', $variant->id)
                        ->whereHas('flashSale', fn (Builder $sales) => $sales
                            ->whereKeyNot($flashSale->id)
                            ->overlapping($flashSale->starts_at, $flashSale->ends_at))
                        ->with('flashSale')
                        ->first();

                    if ($clash !== null) {
                        $validator->errors()->add('product_variant_id', "SKU {$variant->sku} sudah dijual di \"{$clash->flashSale->name}\" yang jadwalnya bentrok.");
                    }
                }
            },
        ];
    }

    /**
     * The offer being updated; null when adding a new one.
     */
    public function offer(): ?FlashSaleItem
    {
        $offer = $this->route('flashSaleItem');

        return $offer instanceof FlashSaleItem ? $offer : null;
    }

    private function flashSale(): FlashSale
    {
        $flashSale = $this->route('flashSale');

        return $flashSale instanceof FlashSale ? $flashSale : ($this->offer()->flashSale ?? abort(404));
    }
}
