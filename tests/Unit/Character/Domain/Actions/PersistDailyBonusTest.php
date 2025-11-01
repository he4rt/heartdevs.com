<?php

declare(strict_types=1);

use Tests\Unit\Character\CharacterProviderTrait;
use Heart\Character\Domain\Actions\PersistDailyBonus;
use Heart\Character\Domain\Exceptions\CharacterException;
use Heart\Character\Domain\Repositories\CharacterRepository;
use Illuminate\Support\Facades\Date;

uses(CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->characterRepository = m::mock(CharacterRepository::class);
    $this->action = new PersistDailyBonus($this->characterRepository);
});
afterEach(function (): void {
    m::close();
});
test('can claim', function (): void {
    $characterId = '123';
    Date::setTestNow(now()->subMinute());
    $characterEntity = $this->validCharacterEntity();
    Date::setTestNow(now()->addDay()->addMinute());

    $this->characterRepository
        ->shouldReceive('findById')
        ->with($characterId)
        ->once()
        ->andReturn($characterEntity);

    $this->characterRepository
        ->shouldReceive('claimDailyBonus')
        ->with($characterEntity)
        ->once();

    $this->action->handle($characterId);
});
test('should not claim', function (): void {
    $this->expectException(CharacterException::class);

    $characterId = '123';
    $characterEntity = $this->validCharacterEntity();

    $this->characterRepository
        ->shouldReceive('findById')
        ->with($characterId)
        ->once()
        ->andReturn($characterEntity);

    $this->action->handle($characterId);
});
