<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Http\Middleware;

use Closure;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetProviderScope
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('provider')) {
            $provider = IdentityProvider::tryFrom($request->query('provider'));

            if ($provider) {
                session(['active_provider' => $provider->value]);
            }
        }

        if (!session()->has('active_provider')) {
            session(['active_provider' => IdentityProvider::Discord->value]);
        }

        return $next($request);
    }
}
