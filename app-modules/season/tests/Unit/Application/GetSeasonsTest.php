<?php

declare(strict_types=1);

use He4rt\Season\Actions\GetSeasons;
use He4rt\Season\Collections\SeasonCollection;
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

test('get season success', function (): void {
    $this->seasonRepositoryStub
        ->shouldReceive('getAll')
        ->once()
        ->andReturn(new SeasonCollection());

    $test = new GetSeasons($this->seasonRepositoryStub);

    $test->handle();
});
