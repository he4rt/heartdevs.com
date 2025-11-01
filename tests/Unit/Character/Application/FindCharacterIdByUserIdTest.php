<?php

declare(strict_types=1);

use Tests\Unit\Character\CharacterProviderTrait;
use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\GetCharacterByUserId;

uses(CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->getCharacterIdByUserId = m::mock(GetCharacterByUserId::class);
    $this->characterEntity = $this->validCharacterEntity();
});
afterEach(function (): void {
    m::close();
});
test('find character by user id', function (): void {
    $this->getCharacterIdByUserId
        ->shouldReceive('handle')
        ->with('canhassi-id')
        ->once()
        ->andReturn($this->characterEntity);

    $test = new FindCharacterIdByUserId($this->getCharacterIdByUserId);

    $test->handle('canhassi-id');
});
