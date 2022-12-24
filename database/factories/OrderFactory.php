<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'amount' => fake()->numberBetween(1000, 10000),
            'discount' => fake()->numberBetween(100, 500),
            'address' => fake()->address(),
            'customer' => fake()->name(),
            'phone' => substr(fake()->e164PhoneNumber(), 1),
            'note' => fake()->sentence(),
            'status' => fake()->numberBetween(1, 5)
        ];
    }
}
