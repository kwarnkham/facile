<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Expense extends Model
{
    use HasFactory, UsesTenantConnection;

    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable');
    }
}
