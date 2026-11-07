<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'attributes' => null,
            'price' => 50000,
            'compare_at_price' => null,
            'stock' => 10,
            'low_stock_threshold' => 5,
            'weight_gram' => 500,
        ];
    }
}
