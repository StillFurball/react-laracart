<?php

namespace Database\Factories;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Offers a Rp100.000 variant for Rp80.000 in a running sale, with no quota or per-customer limit.
 *
 * @extends Factory<FlashSaleItem>
 */
class FlashSaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'flash_sale_id' => FlashSale::factory(),
            'product_variant_id' => ProductVariant::factory()->state(['price' => 100000]),
            'sale_price' => 80000,
            'quota' => null,
            'per_user_limit' => null,
        ];
    }

    /**
     * Cap the units sold at the sale price.
     */
    public function quota(int $quota, int $sold = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'quota' => $quota,
            'sold_count' => $sold,
        ]);
    }

    /**
     * Cap the units one customer may buy at the sale price.
     */
    public function perUserLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'per_user_limit' => $limit,
        ]);
    }
}
