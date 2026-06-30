<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestTenantIdentifier
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (!$panel->hasTenancy()) {
            return $next($request);
        }

        if (!$request->route()->hasParameter('tenant')) {
            return $next($request);
        }

        $tenantSlug = $request->route()->parameter('tenant');

        if (!is_string($tenantSlug)) {
            return $next($request);
        }

        $tenant = $panel->getTenant($tenantSlug);
        Filament::setTenant($tenant, isQuiet: true);

        return $next($request);
    }
}
