<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Exceptions\ExternalIdentityException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Support\Facades\Cache;

class FindExternalIdentity
{
    public function handle(string $provider, string $providerId, ?string $tenantId = null): ExternalIdentity
    {
        $cacheKey = $tenantId !== null
            ? sprintf('provider-%s-%s-%s', $provider, $providerId, $tenantId)
            : sprintf('provider-%s-%s', $provider, $providerId);

        return Cache::remember(
            $cacheKey,
            2 * 86400,
            fn () => $this->find($provider, $providerId, $tenantId)
        );
    }

    private function find(string $provider, string $providerId, ?string $tenantId = null): ExternalIdentity
    {
        $resolvedTenantId = $tenantId ?? request()->input('tenant_id');

        $model = ExternalIdentity::query()
            ->where('tenant_id', $resolvedTenantId)
            ->where('provider', $provider)
            ->where('external_account_id', $providerId)
            ->first();

        throw_unless($model, ExternalIdentityException::notFound($provider, $providerId));

        return $model;
    }
}
