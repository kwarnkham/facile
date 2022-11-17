<?php

namespace Tests\Feature;

use App\Models\MerchantPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    public function test_merchant_add_a_payment()
    {
        $existed = $this->merchant->merchant->payments()->count();
        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => $this->payment->id
        ]);

        $this->assertDatabaseCount('merchant_payments', $existed + 1);

        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => $this->payment->id
        ])->assertSessionHasErrors(['number']);
    }

    public function test_merchant_delete_a_payment()
    {
        $this->actingAs($this->merchant)->post(route('merchant_payments.store'), [
            'number' => '123',
            'payment_id' => $this->payment->id
        ]);
        $payment = $this->merchant->merchant->payments()->first()->pivot;
        $existed = MerchantPayment::count();
        $this->actingAs($this->merchant)->delete(route('merchant_payments.destroy', ['merchantPayment' => $payment->id]))->assertSessionHas('message', 'Deleted');

        $this->assertDatabaseMissing('merchant_payments', $payment->toArray());
        $this->assertDatabaseCount('merchant_payments', $existed - 1);
    }
}
