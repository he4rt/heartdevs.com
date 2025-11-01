<?php

declare(strict_types=1);

use He4rt\Badge\Tests\Unit\BadgeProviderTrait;
use Heart\Badges\Domain\Actions\PersistBadge;
use Heart\Badges\Domain\DTOs\NewBadgeDTO;
use Heart\Badges\Domain\Repositories\BadgeRepository;

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
        $this->badgeEntity->active
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
