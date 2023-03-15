<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProduct extends Pivot
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
        return $this->belongsToMany(Batch::class, foreignPivotKey: 'order_product_id', relatedPivotKey: 'batch_id')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }
}
