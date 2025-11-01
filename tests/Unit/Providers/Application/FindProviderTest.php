<?php

declare(strict_types=1);
use Heart\Provider\Application\FindProvider;
use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Shared\Application\TTL;
use Illuminate\Support\Facades\Cache;

test('cached provider', function (): void {
    $cacheKey = 'provider-twitch-123';
    $getProviderStub = m::mock(GetProviderById::class);

    Cache::shouldReceive('remember')
        ->once()
        ->with($cacheKey, TTL::fromDays(2), m::type('closure'))
        ->andReturn(new ProviderEntity(1, 1, 1, 1, '1'));

    $action = new FindProvider($getProviderStub);

    $result = $action->handle('twitch', '123');

    expect($result)->toBeInstanceOf(ProviderEntity::class);
});
