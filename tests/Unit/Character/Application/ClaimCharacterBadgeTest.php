<?php

declare(strict_types=1);

use He4rt\Badge\Tests\Unit\BadgeProviderTrait;
use Tests\Unit\Character\ProviderProviderTrait;
use Heart\Badges\Application\FindBadgeBySlug;
use Heart\Character\Application\ClaimCharacterBadge;
use Heart\Character\Application\FindCharacterIdByUserId;
use Heart\Character\Domain\Actions\PersistClaimedBadge;
use Heart\Provider\Application\FindProvider;

uses(BadgeProviderTrait::class);

uses(ProviderProviderTrait::class);

beforeEach(function (): void {
    $this->persistClaimBadgeStub = m::mock(PersistClaimedBadge::class);
    $this->findProviderStub = m::mock(FindProvider::class);
    $this->findCharacterIdByUserId = m::mock(FindCharacterIdByUserId::class);
    $this->findBadgeBySlug = m::mock(FindBadgeBySlug::class);
    $this->providerEntity = $this->validProviderEntity();
    $this->badgeEntity = $this->validBadgeEntity();
});
afterEach(function (): void {
    m::close();
});
test('claim character badge success', function (): void {
    $this->findProviderStub
        ->shouldReceive('handle')
        ->with('canhassi-provider', 'canhassi-id')
        ->once()
        ->andReturn($this->providerEntity);

    $this->findCharacterIdByUserId
        ->shouldReceive('handle')
        ->with($this->providerEntity->userId)
        ->once()
        ->andReturn('character-id');

    $this->findBadgeBySlug
        ->shouldReceive('handle')
        ->with('é o canhas')
        ->once()
        ->andReturn($this->badgeEntity);

    $this->persistClaimBadgeStub
        ->shouldReceive('handle')
        ->with('character-id', $this->badgeEntity->id)
        ->once();

    $test = new ClaimCharacterBadge(
        $this->persistClaimBadgeStub,
        $this->findProviderStub,
        $this->findCharacterIdByUserId,
        $this->findBadgeBySlug
    );

    $test->handle('canhassi-provider', 'canhassi-id', 'é o canhas');
});
