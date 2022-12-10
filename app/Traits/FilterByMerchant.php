<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait FilterByMerchant
{
    public static function boot()
    {
        parent::boot();

        $currentMerchantID = auth()->user() ? auth()->user()->merchant->id : null;

        self::creating(function ($model) use ($currentMerchantID) {
            if ($currentMerchantID)
                $model->merchant_id = $currentMerchantID;
        });

        self::addGlobalScope(function (Builder $builder) use ($currentMerchantID) {
            if ($currentMerchantID)
                $builder->where('merchant_id', $currentMerchantID);
        });
    }
}
