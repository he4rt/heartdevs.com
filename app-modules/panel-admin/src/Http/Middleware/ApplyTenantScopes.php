<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if (!$tenant) {
            return $next($request);
        }

        foreach (config('panel-admin.tenant_scoped_models', []) as $model) {
            $model::addGlobalScope(
                'tenant',
                fn (Builder $query) => $query->whereBelongsTo($tenant),
            );
        }

        return $next($request);
    }
}
