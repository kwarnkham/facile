<?php

namespace Tests\Feature;

use App\Models\Expense;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    protected $tenancy = true;
    public function test_create_expense()
    {
        $dataExpense = Expense::factory()->make();

        $this->actingAs($this->user)->post(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_record_expense()
    {
        $this->actingAs($this->user)->post(
            route(
                'expenses.record',
                ['expense' => Expense::factory()->create()->id]
            ),
            ['price' => rand(1000, 100000)]
        );

        $this->assertDatabaseCount('purchases', 1);
    }
}
