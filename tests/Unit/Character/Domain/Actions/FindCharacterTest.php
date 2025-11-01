<?php

declare(strict_types=1);

use Tests\Unit\Character\CharacterProviderTrait;
use Heart\Character\Domain\Actions\FindCharacter;
use Heart\Character\Domain\Repositories\CharacterRepository;

uses(CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->characterRepositoryStub = m::mock(CharacterRepository::class);
    $this->characterEntity = $this->validCharacterEntity();
});
afterEach(function (): void {
    m::close();
});
test('find character success', function (): void {
    $this->characterRepositoryStub
        ->shouldReceive('findById')
        ->with($this->characterEntity->id)
        ->once()
        ->andReturn($this->characterEntity);

    $test = new FindCharacter($this->characterRepositoryStub);

    $test->handle($this->characterEntity->id);
});
