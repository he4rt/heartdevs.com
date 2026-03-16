<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Exceptions\ExternalIdentityException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Support\Facades\Cache;

class FindExternalIdentity
{
    public function handle(string $provider, string $providerId): ExternalIdentity
    {
        $cacheKey = sprintf('provider-%s-%s', $provider, $providerId);

        return Cache::remember(
            $cacheKey,
            2 * 86400,
            fn () => $this->find($provider, $providerId)
        );
    }

    private function find(string $provider, string $providerId): ExternalIdentity
    {
        $model = ExternalIdentity::query()
            ->where('tenant_id', request()->input('tenant_id'))
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        throw_unless($model, ExternalIdentityException::notFound($provider, $providerId));

        return $model;
    }
}
