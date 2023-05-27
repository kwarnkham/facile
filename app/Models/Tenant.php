<?php

namespace App\Models;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected static function booted()
    {
        static::creating(fn (Tenant $model) => $model->createDatabase());
    }

    public function createDatabase()
    {
        config(['database.connections.tenant.database' => $this->database]);
        DB::purge('tenant');
        DB::statement("CREATE DATABASE `{$this->database}`");
        Artisan::call('migrate --database=tenant --path=database/migrations/tenant --force');
        Artisan::call('db:seed --database=tenant --force');
    }
}
