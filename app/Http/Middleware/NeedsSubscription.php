<?php

namespace App\Http\Middleware;

use App\Enums\ResponseStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NeedsSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('currentTenant');
        if (!is_null($tenant->expires_on) && today()->greaterThan($tenant->expires_on)) {
            abort(ResponseStatus::UNAUTHORIZED->value, 'Subscription has expired');
        }
        return $next($request);
    }
}
