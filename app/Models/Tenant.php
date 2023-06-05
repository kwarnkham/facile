<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected static function booted()
    {
        static::creating(fn (Tenant $tenant) => $tenant->createDatabase());

        static::deleting(fn (Tenant $tenant) => $tenant->deleteDatabase());
    }

    public function deleteDatabase()
    {
        if (App::isProduction())
            Artisan::call('tenants:artisan "backup:run --only-db" --tenant=' . $this->id);
        DB::connection('mysql')->statement("DROP DATABASE `{$this->database}`");
    }

    protected $casts = ['expires_on' => 'datetime'];

    public function scopeFilter(Builder $query, $filters)
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $query, $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('domain', 'like', '%' . $search . '%');
            })
        );

        $query->when(
            $filters['active'] ?? null,
            fn (Builder $query) => $query->where(function (Builder $query) {
                $query->whereDate('expires_on', '>=', today())->orWhereNull('expires_on');
            })
        );

        $query->when(
            $filters['expired'] ?? null,
            fn (Builder $query) => $query->whereDate('expires_on', '<', today())
        );
    }

    public function createDatabase()
    {

        DB::connection('tenant')->statement("CREATE DATABASE `{$this->database}`");
        config(['database.connections.tenant.database' => $this->database]);
        DB::purge('tenant');
        $this->makeCurrent();
        Artisan::call('migrate --database=tenant --path=database/migrations/tenant --force');
        Artisan::call('db:seed --database=tenant --force');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
