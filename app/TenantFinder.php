<?php

namespace App;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use App\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as BaseTenantFinder;

class TenantFinder extends BaseTenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $tenant = $request->header('Tenant');
        if (!$tenant) $tenant = $request->tenant;
        if (!$tenant) return null;
        return $this->getTenantModel()::whereDomain($tenant)->first();
    }
}
