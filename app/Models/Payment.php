<?php

namespace App\Models;

use App\Enums\ResponseStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Payment extends Model
{
    use HasFactory, UsesTenantConnection;

    public function qr(): Attribute
    {
        $tenant = app('currentTenant');

        abort_if(is_null($tenant), ResponseStatus::BAD_REQUEST->value, 'No tenant found');

        return Attribute::make(
            fn ($value) => $value ? Storage::url(
                $tenant->domain . '/payments' .  '/' . $value
            ) : $value
        );
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
}
