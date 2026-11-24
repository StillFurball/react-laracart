<?php

namespace App\Http\Requests\Admin;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FlashSaleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * A variant may be in only one sale at a time, so a new schedule must not overlap another sale
     * that offers any of the same variants.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $flashSale = $this->route('flash_sale');
                $startsAt = $this->date('starts_at');
                $endsAt = $this->date('ends_at');

                if ($validator->errors()->isNotEmpty() || ! $flashSale instanceof FlashSale || $startsAt === null || $endsAt === null) {
                    return;
                }

                $clash = FlashSaleItem::query()
                    ->whereIn('product_variant_id', $flashSale->items()->select('product_variant_id'))
                    ->whereHas('flashSale', fn (Builder $sales) => $sales
                        ->whereKeyNot($flashSale->id)
                        ->overlapping($startsAt, $endsAt))
                    ->with(['flashSale', 'variant'])
                    ->first();

                if ($clash !== null) {
                    $validator->errors()->add('starts_at', "Jadwal bentrok dengan \"{$clash->flashSale->name}\", yang juga menjual SKU {$clash->variant->sku}.");
                }
            },
        ];
    }
}
