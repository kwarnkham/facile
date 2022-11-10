<?php

namespace Tests\Feature;

use App\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DiscountTest extends TestCase
{

    public function test_create_a_discount()
    {
        $discount = Discount::factory()->make()->toArray();
        $this->actingAs($this->merchant)->post(route('discounts.store'), $discount);

        $this->assertDatabaseCount('discounts', 1);
        $this->assertDatabaseHas('discounts', $discount);

        $this->actingAs($this->merchant)->post(route('discounts.store'), $discount)->assertSessionHasErrors(['name']);
    }
}
