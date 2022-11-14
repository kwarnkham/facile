<?php

namespace Tests\Unit;

use App\Models\Discount;
use App\Models\Feature;
use App\Models\Item;
use Tests\TestCase;

class ItemFeatureTest extends TestCase
{
    public function test_get_total_discounts()
    {
        $data = ['merchant_id' => $this->merchant->merchant->id];
        $feature = Feature::factory()
            ->for(Item::factory()->state($data))
            ->has(Discount::factory(rand(1, 3))->state($data))
            ->create();

        $this->assertEquals(
            $feature->totalDiscount(),
            floor((float)$feature->discounts->reduce(
                fn ($carry, $discount) => $carry + $discount->percentage,
                0
            ) / 100 * $feature->price)
        );
    }
}
