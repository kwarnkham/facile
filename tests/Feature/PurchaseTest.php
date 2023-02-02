<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Purchase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{

    public function test_create_a_purchase_for_expense()
    {
        $expnese = Expense::factory()->create();
        $this->actingAs($this->user)->post(route('purchases.store'), [
            'price' => rand(1000, 100000),
            'quantity' => 2,
            'type' => 'expense',
            'type_id' => $expnese->id,
            'name' => 'name'
        ])->assertSessionHas('message', 'Success');
    }

    public function test_assign_a_purchase_to_a_group()
    {
        $expnese = Expense::factory()->create();
        $this->actingAs($this->user)->post(route('purchases.store'), [
            'price' => rand(1000, 100000),
            'quantity' => 2,
            'type' => 'expense',
            'type_id' => $expnese->id,
            'name' => 'name'
        ]);

        $this->assertDatabaseCount('purchases', 1);
        $purchase = Purchase::first();
        $this->assertEquals($purchase->group, 0);
        $group = 1;
        $this->actingAs($this->user)->post(route('purchases.group', ['purchase' => $purchase->id]), [
            'group' => $group
        ]);

        $this->assertEquals($group, $purchase->fresh()->group);
    }
}
