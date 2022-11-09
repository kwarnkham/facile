<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    public function test_merchant_add_a_payment()
    {
        $existed = $this->merchant->payments()->count();
        $this->actingAs($this->merchant)->post(route('user_payments.store'), [
            'number' => '123',
            'payment_id' => $this->payment->id
        ]);

        $this->assertDatabaseCount('user_payments', $existed + 1);

        $this->actingAs($this->merchant)->post(route('user_payments.store'), [
            'number' => '123',
            'payment_id' => $this->payment->id
        ])->assertSessionHasErrors(['number']);
    }
}
