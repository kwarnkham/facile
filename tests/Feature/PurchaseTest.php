<?php

namespace Tests\Product;

use App\Models\Expense;
use App\Models\Purchase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_assign_a_purchase_to_a_group()
    {
        $expnese = Expense::factory()->create();
        Purchase::factory()->state(['name' => 'name'])->for(Expense::factory(), 'purchasable')->create();

        $this->assertDatabaseCount('purchases', 1);
        $purchase = Purchase::first();
        $this->assertEquals($purchase->group, 0);
        $group = 1;
        $this->actingAs($this->user)->postJson(route('purchases.group', ['purchase' => $purchase->id]), [
            'group' => $group
        ]);

        $this->assertEquals($group, $purchase->fresh()->group);
    }
}
