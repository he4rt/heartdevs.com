<?php

declare(strict_types=1);

use He4rt\Provider\Actions\GetProviderById;
use He4rt\Provider\Contracts\ProviderRepository;
use He4rt\Provider\Entities\ProviderEntity;
use Mockery as m;

beforeEach(function (): void {});
test('get provider by id', function (): void {
    $providerRepositoryStub = m::mock(ProviderRepository::class);

    $providerRepositoryStub
        ->shouldReceive('findByProvider')
        ->once()
        ->with('twitch', '12345678')
        ->andReturn(new ProviderEntity('1', '1', '1', '1', '1', 'email@foda.com'));

    $action = new GetProviderById($providerRepositoryStub);

    $result = $action->handle('twitch', '12345678');
    expect($result)->toBeInstanceOf(ProviderEntity::class);
});
