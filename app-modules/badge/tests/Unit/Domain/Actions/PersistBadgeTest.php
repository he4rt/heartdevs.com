<?php

declare(strict_types=1);

use He4rt\Badge\Actions\PersistBadge;
use He4rt\Badge\Contracts\BadgeRepository;
use He4rt\Badge\DTOs\NewBadgeDTO;
use He4rt\Badge\Tests\Unit\BadgeProviderTrait;
use Mockery as m;

uses(BadgeProviderTrait::class);

beforeEach(function (): void {
    $this->badgeRepositoryStub = m::mock(BadgeRepository::class);
    $this->badgeEntity = $this->validBadgeEntity();
    $this->badgeDTO = new NewBadgeDTO(
        'canhassi', // provider
        $this->badgeEntity->name,
        $this->badgeEntity->description,
        'https://canhassi.tech', // image URL
        $this->badgeEntity->redeemCode,
        $this->badgeEntity->active,
        $this->badgeEntity->tenantId
    );
});

afterEach(function (): void {
    m::close();
});

test('persist badge success', function (): void {
    $this->badgeRepositoryStub
        ->shouldReceive('create')
        ->with($this->badgeDTO)
        ->once()
        ->andReturn($this->badgeEntity);

    $test = new PersistBadge($this->badgeRepositoryStub);

    $test->handle($this->badgeDTO);
});
