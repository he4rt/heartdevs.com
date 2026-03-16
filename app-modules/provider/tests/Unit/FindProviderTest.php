<?php

declare(strict_types=1);

use He4rt\Provider\Actions\FindProvider;
use He4rt\Provider\Actions\GetProviderById;
use He4rt\Provider\Contracts\ProviderRepository;
use He4rt\Provider\Entities\ProviderEntity;
use Illuminate\Support\Facades\Cache;

test('cached provider', function (): void {
    $cacheKey = 'provider-twitch-123';

    $providerRepositoryStub = Mockery::mock(ProviderRepository::class);
    $getProvider = new GetProviderById($providerRepositoryStub);
    $providerEntity = new ProviderEntity('1', '1', '1', '1', '1', '1');

    Cache::shouldReceive('remember')
        ->once()
        ->with($cacheKey, 2 * 86400, Mockery::type('closure'))
        ->andReturn($providerEntity);

    $action = new FindProvider($getProvider);
    $result = $action->handle('twitch', '123');

    expect($result)->toBeInstanceOf(ProviderEntity::class);
});
