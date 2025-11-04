<?php

declare(strict_types=1);

use He4rt\Character\Actions\ClaimDailyBonus;
use He4rt\Character\Actions\FindCharacterIdByUserId;
use He4rt\Character\Actions\PersistDailyBonus;
use He4rt\Character\Contracts\CharacterRepository;
use He4rt\Character\Tests\Unit\CharacterProviderTrait;
use He4rt\Character\Tests\Unit\ProviderProviderTrait;
use He4rt\Provider\Actions\FindProvider;

uses(ProviderProviderTrait::class, CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->characterRepositoryStub = Mockery::mock(CharacterRepository::class);
    $this->persistDailyBonus = new PersistDailyBonus($this->characterRepositoryStub);
    $this->findProviderStub = Mockery::mock(FindProvider::class);
    $this->findCharacterIdByUserIdStub = Mockery::mock(FindCharacterIdByUserId::class);
    $this->providerEntity = $this->validProviderEntity();
    $this->characterEntity = $this->validCharacterEntity([
        'daily_bonus_claimed_at' => null,
    ]);
});

afterEach(function (): void {
    Mockery::close();
});

test('claim daily bonus success', function (): void {
    $this->findProviderStub
        ->shouldReceive('handle')
        ->with('canhassi-provider', 'canhassi-id')
        ->once()
        ->andReturn($this->providerEntity);

    $this->findCharacterIdByUserIdStub
        ->shouldReceive('handle')
        ->with($this->providerEntity->modelId)
        ->once()
        ->andReturn('character-id');

    $this->characterRepositoryStub
        ->shouldReceive('findById')
        ->with('character-id')
        ->once()
        ->andReturn($this->characterEntity);

    $this->characterRepositoryStub
        ->shouldReceive('claimDailyBonus')
        ->once();

    $test = new ClaimDailyBonus(
        $this->persistDailyBonus,
        $this->findProviderStub,
        $this->findCharacterIdByUserIdStub
    );

    $test->handle('canhassi-provider', 'canhassi-id');
});
