<?php

namespace Tests\Product;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Picture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentTest extends TestCase
{

    public function test_add_a_payment()
    {
        $existed = Payment::count();
        $payment = Payment::factory()->make()->toArray();
        $payment['payment_type_id'] = $this->payment_type_id_2;
        $payment['qr'] = UploadedFile::fake()->image('qr.jpg');

        $this->actingAs($this->user)->postJson(route('payments.store'), $payment);
        $this->assertTrue(Picture::deletePictureFromDisk($payment['qr']->hashName(), 'payments'));
        $this->assertDatabaseCount('payments', $existed + 1);

        $this->actingAs($this->user)->postJson(route('payments.store'), $payment)->assertUnprocessable();
    }

    public function test_toggle_a_payment()
    {
        $this->actingAs($this->user)->postJson(route('payments.store'), [
            'number' => '123',
            'payment_type_id' => $this->payment_type_id
        ]);
        $payment = Payment::first();

        $this->actingAs($this->user)->postJson(route('payments.toggle', ['payment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, PaymentStatus::DISABLED->value);

        $this->actingAs($this->user)->postJson(route('payments.toggle', ['payment' => $payment->id]));

        $this->assertEquals($payment->fresh()->status, PaymentStatus::ENABLED->value);
    }

    public function test_update_payment()
    {
        $payment = Payment::factory()->make()->toArray();
        $payment['payment_type_id'] = $this->payment_type_id_2;
        $payment['qr'] = UploadedFile::fake()->image('qr.jpg');

        $this->actingAs($this->user)->postJson(route('payments.store'), $payment);

        $payment = Payment::orderBy('id', 'desc')->first();


        $updatedPayment = Payment::factory()->make()->toArray();
        $updatedPayment['qr'] = UploadedFile::fake()->image('qr.jpg');
        $this->actingAs($this->user)->put(
            route('payments.update', ['payment' => $payment->id]),
            $updatedPayment
        );

        $this->assertFalse(Storage::exists(Picture::picturePath($payment->getRawOriginal('qr'), 'order_payments')));
        $payment->refresh();
        $this->assertTrue(Picture::deletePictureFromDisk($payment->getRawOriginal('qr'), 'payments'));
        $this->assertEquals($updatedPayment['number'], $payment->number);
        $this->assertEquals($updatedPayment['account_name'], $payment->account_name);
        $this->assertEquals($updatedPayment['qr']->hashName(), $payment->getRawOriginal('qr'));
    }
}
