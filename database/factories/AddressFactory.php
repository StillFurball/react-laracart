<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipient_name' => fake()->name(),
            'phone' => '0812'.fake()->numerify('########'),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => 'Jawa Barat',
            'postal_code' => fake()->numerify('#####'),
        ];
    }
}
