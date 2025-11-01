<?php

declare(strict_types=1);

use Tests\Unit\Character\ProviderProviderTrait;
use Heart\Character\Application\ClaimDailyBonus;
use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Provider\Application\FindProvider;

uses(ProviderProviderTrait::class);

beforeEach(function (): void {
    $this->persistDailyStub = m::mock(PersistDailyBonus::class);
    $this->findProviderStub = m::mock(FindProvider::class);
    $this->findCharacterIdByUserId = m::mock(FindCharacterIdByUserId::class);
    $this->providerEntity = $this->validProviderEntity();
});
afterEach(function (): void {
    m::close();
});
test('claim daily bonus success', function (): void {
    $this->findProviderStub
        ->shouldReceive('handle')
        ->with('canhassi-provider', 'canhassi-id')
        ->once()
        ->andReturn($this->providerEntity);

    $this->findCharacterIdByUserId
        ->shouldReceive('handle')
        ->with($this->providerEntity->userId)
        ->once()
        ->andReturn('character-id');

    $this->persistDailyStub
        ->shouldReceive('handle')
        ->with('character-id')
        ->once();

    $test = new ClaimDailyBonus(
        $this->persistDailyStub,
        $this->findProviderStub,
        $this->findCharacterIdByUserId
    );

    $test->handle('canhassi-provider', 'canhassi-id');
});
