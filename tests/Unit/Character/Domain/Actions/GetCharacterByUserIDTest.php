<?php

declare(strict_types=1);

use Tests\Unit\Character\CharacterProviderTrait;
use Heart\Character\Domain\Actions\GetCharacterByUserId;
use Heart\Character\Domain\Repositories\CharacterRepository;

uses(CharacterProviderTrait::class);

beforeEach(function (): void {
    $this->characterRepositoryStub = m::mock(CharacterRepository::class);
    $this->characterEntity = $this->validCharacterEntity();
});
afterEach(function (): void {
    m::close();
});
test('get character by user id', function (): void {
    $this->characterRepositoryStub
        ->shouldReceive('findByUserId')
        ->with(12)
        ->once()
        ->andReturn($this->characterEntity);

    $test = new GetCharacterByUserId($this->characterRepositoryStub);

    $test->handle(12);
});
