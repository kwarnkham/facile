<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Merchant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;
    public function test_create_expense()
    {
        $dataExpense = Expense::factory()->make(['merchant_id' => $this->merchant->merchant->id]);

        $this->actingAs($this->merchant)->post(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 1);

        $merchant = Merchant::factory()->create();
        $user = User::factory()->create();
        $user->merchants()->attach($merchant);
        $user->active_merchant_id = $merchant->id;
        $user->save();

        $this->actingAs(User::find($user->id))->post(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 2);

        $this->actingAs(User::find($user->id))->post(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_record_expense()
    {
        $this->actingAs($this->merchant)->post(
            route(
                'expenses.record',
                ['expense' => Expense::factory()->create(['merchant_id' => $this->merchant->merchant->id])]
            ),
            ['price' => rand(1000, 100000)]
        );

        $this->assertDatabaseCount('purchases', 1);
    }
}
