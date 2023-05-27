<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Order extends Model
{
    use HasFactory, UsesTenantConnection;

    public function reverseStock()
    {
        $this->aItems->each(function ($aItem) {
            if ($aItem->type == ProductType::STOCKED->value)
                DB::connection('tenant')->table('a_items')->where('id', $aItem->id)->increment('stock', $aItem->pivot->quantity);
        });
        $this->aItems()->detach();
    }


    public function purchases()
    {
        return $this->morphMany(Purchase::class, 'purchasable')->where('status', PurchaseStatus::NORMAL->value);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class)
            ->withPivot(['price', 'quantity', 'discount', 'cost', 'name'])
            ->withTimestamps();
    }

    public function aItems()
    {
        return $this->belongsToMany(AItem::class)
            ->withPivot(['price', 'quantity', 'name', 'discount', 'purchase_price'])
            ->withTimestamps();
    }


    public function paidAmount()
    {
        return (float)$this->payments->reduce(fn ($carry, $payment) => $payment->pivot->amount + $carry, 0);
    }

    public function scopeFilter(Builder $query, $filters)
    {
        $query
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query) => $query->whereIn(
                    'status',
                    explode(',', $filters['status'])
                )
            )
            ->when(
                $filters['from'] ?? null,
                fn (Builder $query, $from) => $query->whereDate(
                    'updated_at',
                    '>=',
                    $from
                )
            )
            ->when(
                $filters['to'] ?? null,
                fn (Builder $query, $to) => $query->whereDate(
                    'updated_at',
                    '<=',
                    $to
                )
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, $search) => $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('phone', 'like',  '%' . $search . '%');
                })
            );
    }
}
