<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    use HasFactory;

    public function qr(): Attribute
    {
        return Attribute::make(
            fn ($value) => $value ? Storage::url(
                config('app')['name'] . '/payments/' . config('app')['env'] . '/' . $value
            ) : $value
        );
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Payment::class)
            ->withPivot([
                'amount', 'number', 'note', 'picture', 'payment_name', 'account_name', 'id'
            ])->withTimestamps();
    }
}
