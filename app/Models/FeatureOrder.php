<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class FeatureOrder extends Pivot
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    public function batches()
    {
        return $this->belongsToMany(Batch::class, foreignPivotKey: 'feature_order_id', relatedPivotKey: 'batch_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}
