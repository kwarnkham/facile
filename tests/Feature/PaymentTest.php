<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    public function test_merchant_add_a_payment()
    {
        $existed = $this->merchant->merchant->payments()->count();
        $payment = Payment::factory()->create();
        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => $payment->id
        ]);

        $this->assertDatabaseCount('merchant_payments', $existed + 1);

        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => $payment->id
        ])->assertSessionHasErrors(['number']);
    }

    public function test_merchant_toggle_a_payment()
    {
        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => Payment::factory()->create()->id
        ]);
        $payment = $this->merchant->merchant->payments()->first()->pivot;
        $this->actingAs($this->merchant)->post(route('merchant_payments.toggle', ['merchantPayment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, 2);

        $this->actingAs($this->merchant)->post(route('merchant_payments.toggle', ['merchantPayment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, 1);
    }
}
