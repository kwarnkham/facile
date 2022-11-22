<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

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

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_payment', 'order_id', 'merchant_payment_id')->withPivot('amount', 'number', 'note', 'picture', 'id')->withTimestamps();
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    protected function picture(): Attribute
    {
        try {
            $picture = Picture::picturePath($this->pivot->picture, 'payments');
        } catch (\Throwable $th) {
            $picture = null;
        }
        return Attribute::make(
            get: fn () => Storage::url($picture)
        );
    }
}
