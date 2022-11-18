<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MerchantPayment extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_payments';
    protected $with = ['payment'];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_payment', 'order_id', 'merchant_payment_id')->withPivot('amount', 'number', 'note')->withTimestamps();
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
