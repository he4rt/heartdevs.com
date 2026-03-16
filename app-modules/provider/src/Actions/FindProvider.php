<?php

declare(strict_types=1);

namespace He4rt\Provider\Actions;

use He4rt\Provider\Entities\ProviderEntity;
use He4rt\Provider\Exceptions\ProviderException;
use He4rt\Provider\Models\Provider;
use Illuminate\Support\Facades\Cache;

class FindProvider
{
    public function handle(string $provider, string $providerId): ProviderEntity
    {
        $providerCacheKey = sprintf('provider-%s-%s', $provider, $providerId);

        return Cache::remember(
            $providerCacheKey,
            2 * 86400,
            fn () => $this->findProvider($provider, $providerId)
        );
    }

    private function findProvider(string $provider, string $providerId): ProviderEntity
    {
        $model = Provider::query()
            ->where('tenant_id', request()->input('tenant_id'))
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        throw_unless($model, ProviderException::notFound($provider, $providerId));

        return ProviderEntity::make($model->toArray());
    }
}
