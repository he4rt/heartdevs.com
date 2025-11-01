<?php

declare(strict_types=1);

use Tests\Unit\Character\CharacterProviderTrait;
use Heart\Character\Domain\Actions\ManageReputation;
use Heart\Character\Domain\Repositories\CharacterRepository;

uses(CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->characterRepository = m::mock(CharacterRepository::class);
    $this->manageReputation = new ManageReputation($this->characterRepository);
});
afterEach(function (): void {
    m::close();
});
test('add reputation', function (): void {
    $character = $this->validCharacterEntity();
    $characterId = 'porra-careca';

    $this->characterRepository
        ->shouldReceive('findById')
        ->once()
        ->with($characterId)
        ->andReturn($character);

    $this->characterRepository
        ->shouldReceive('updateReputation')
        ->once()
        ->with($character);

    $this->manageReputation->handle($characterId, 'increment');
});
