<?php

namespace Database\Factories;

use App\Enums\VoucherType;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => VoucherType::Fixed,
            'value' => 10000,
            'max_discount' => null,
            'min_purchase' => 0,
            'quota' => null,
            'per_user_limit' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate a percentage voucher.
     */
    public function percent(int $value, ?int $maxDiscount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => VoucherType::Percent,
            'value' => $value,
            'max_discount' => $maxDiscount,
        ]);
    }
}
