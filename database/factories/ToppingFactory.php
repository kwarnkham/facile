<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Topping>
 */
class ToppingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $price = fake()->numberBetween(1000, 10000);
        return [
            'name' => fake()->unique()->name(),
            'price' => $price,
            'cost' => floor($price / 2)
        ];
    }
}
