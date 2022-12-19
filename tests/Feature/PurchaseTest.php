<?php

namespace Tests\Feature;

use App\Models\Expense;
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
            'type_id' => $expnese->id
        ])->assertSessionHas('message', 'Success');
    }
}
