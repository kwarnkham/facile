<?php

namespace Tests\Feature;

use App\Models\Topping;
use Tests\TestCase;

class ToppingTest extends TestCase
{
    public function test_create_topping()
    {
        $topping = Topping::factory()->make();
        $this->actingAs($this->user)
            ->post(route('toppings.store'), $topping->toArray());

        $this->assertDatabaseCount('toppings', 1);
        $this->assertDatabaseHas('toppings', $topping->toArray());
    }

    public function test_update_topping()
    {
        Topping::factory()->create();
        $updatedTopping = Topping::factory()->make();
        $this->actingAs($this->user)
            ->post(route('toppings.store'), $updatedTopping->toArray());

        $this->assertDatabaseHas('toppings', $updatedTopping->toArray());
    }
}
