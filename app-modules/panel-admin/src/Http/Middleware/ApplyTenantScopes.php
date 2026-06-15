<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if (!$tenant) {
            return $next($request);
        }

        /** @var array<int, class-string<Model>> $models */
        $models = config('panel-admin.tenant_scoped_models', []);

        foreach ($models as $model) {
            $model::addGlobalScope(
                'tenant',
                fn (Builder $query) => $query->whereBelongsTo($tenant),
            );
        }

        return $next($request);
    }
}
