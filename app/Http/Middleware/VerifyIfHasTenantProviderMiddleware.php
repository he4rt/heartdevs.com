<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class VerifyIfHasTenantProviderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasHeader('X-He4rt-Provider') || ! $request->hasHeader('X-He4rt-Provider-Id')) {
            return response()->json(['error' => 'Provider not registed for any tenant.'], Response::HTTP_UNAUTHORIZED);
        }

        $provider = ProviderEnum::tryFrom($request->header('X-He4rt-Provider') ?? 'not-found');

        if (! $provider) {
            return response()->json(['error' => 'Provider not registed for any tenant.'], Response::HTTP_UNAUTHORIZED);
        }

        $providerId = $request->header('X-He4rt-Provider-Id');

        $str = sprintf('tenant_provider_%s', $providerId);
        $providerModel = Cache::flexible($str, [1, 2], fn () => Provider::query()
            ->where('provider_id', $providerId)
            ->where('provider', $provider)
            ->first()
        );

        if (! $providerModel) {
            return response()->json(['error' => 'Provider not registed for any tenant.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->merge(['tenant_id' => $providerModel->tenant_id]);

        return $next($request);
    }
}
