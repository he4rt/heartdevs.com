<?php

declare(strict_types=1);

use He4rt\Season\Actions\GetCurrentSeason;
use He4rt\Season\Contracts\SeasonRepository;
use He4rt\Season\Tests\Unit\SeasonProviderTrait;

uses(SeasonProviderTrait::class);

beforeEach(function (): void {
    $this->seasonRepositoryStub = Mockery::mock(SeasonRepository::class);
    $this->seasonEntity = $this->validSeasonEntity();
});

afterEach(function (): void {
    Mockery::close();
});

test('get current season', function (): void {
    $this->seasonRepositoryStub
        ->shouldReceive('getCurrent')
        ->once()
        ->andReturn($this->seasonEntity);

    $test = new GetCurrentSeason($this->seasonRepositoryStub);

    $test->handle();
});
