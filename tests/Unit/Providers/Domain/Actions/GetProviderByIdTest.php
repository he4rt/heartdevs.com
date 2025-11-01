<?php

declare(strict_types=1);
use Heart\Provider\Domain\Actions\GetProviderById;
use Heart\Provider\Domain\Entities\ProviderEntity;
use Heart\Provider\Domain\Repositories\ProviderRepository;

beforeEach(function (): void {});
test('get provider by id', function (): void {
    $providerRepositoryStub = m::mock(ProviderRepository::class);

    $providerRepositoryStub
        ->shouldReceive('findByProvider')
        ->once()
        ->with('twitch', '12345678')
        ->andReturn(new ProviderEntity(1, 1, 1, 1, 'email@foda.com'));

    $action = new GetProviderById($providerRepositoryStub);

    $result = $action->handle('twitch', '12345678');
    expect($result)->toBeInstanceOf(ProviderEntity::class);
});
