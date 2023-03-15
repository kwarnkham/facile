<?php

namespace Tests\Product;

use App\Models\Expense;
use App\Models\Picture;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    public function test_create_expense()
    {
        $dataExpense = Expense::factory()->make();

        $this->actingAs($this->user)->postJson(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_record_expense()
    {
        $this->actingAs($this->user)->postJson(
            route(
                'expenses.record',
                ['expense' => Expense::factory()->create()->id]
            ),
            ['price' => rand(1000, 100000)]
        );

        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_record_expense_with_picture()
    {
        $image = UploadedFile::fake()->image('foo.jpg');
        $this->actingAs($this->user)->postJson(
            route(
                'expenses.record',
                ['expense' => Expense::factory()->create()->id]
            ),
            ['price' => rand(1000, 100000), 'note' => 'the note', 'picture' => $image]
        );

        $this->assertDatabaseCount('purchases', 1);
        $this->assertTrue(Picture::deletePictureFromDisk($image->hashName(), 'purchases'));
    }

    public function test_update_an_expense()
    {
        $dataExpense = Expense::factory()->make();

        $this->actingAs($this->user)->postJson(
            route('expenses.store'),
            $dataExpense->toArray()
        );
        $this->assertDatabaseCount('expenses', 1);

        $expense = Expense::first();

        $dataExpense = Expense::factory()->make();

        $this->actingAs($this->user)->put(
            route('expenses.update', ['expense' => $expense->id]),
            $dataExpense->toArray()
        );

        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseHas('expenses', $dataExpense->toArray());
    }
}
