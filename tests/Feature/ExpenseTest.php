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

        $user = User::factory()->has(Merchant::factory())->create();
        $user->roles()->attach(Role::where('name', 'merchant')->first());
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
