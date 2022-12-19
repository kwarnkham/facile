<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Tests\TestCase;

class PaymentTest extends TestCase
{

    public function test_add_a_payment()
    {
        $existed = Payment::count();
        $this->actingAs($this->user)->post(route('payments.store'), [
            'number' => '123',
            'payment_type_id' => $this->payment_type_id
        ]);

        $this->assertDatabaseCount('payments', $existed + 1);

        $this->actingAs($this->user)->post(route('payments.store'), [
            'number' => '123',
            'payment_type_id' => $this->payment_type_id
        ])->assertSessionHasErrors(['number']);
    }

    public function test_toggle_a_payment()
    {
        $this->actingAs($this->user)->post(route('payments.store'), [
            'number' => '123',
            'payment_type_id' => $this->payment_type_id
        ]);
        $payment = Payment::first();

        $this->actingAs($this->user)->post(route('payments.toggle', ['payment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, PaymentStatus::DISABLED->value);

        $this->actingAs($this->user)->post(route('payments.toggle', ['payment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, PaymentStatus::ENABLED->value);
    }
}
