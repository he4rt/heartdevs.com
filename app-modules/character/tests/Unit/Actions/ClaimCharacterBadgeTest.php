<?php

declare(strict_types=1);

use He4rt\Badge\Actions\FindBadgeBySlug;
use He4rt\Badge\Contracts\BadgeRepository;
use He4rt\Badge\Tests\Unit\BadgeProviderTrait;
use He4rt\Character\Actions\ClaimCharacterBadge;
use He4rt\Character\Actions\FindCharacterIdByUserId;
use He4rt\Character\Actions\PersistClaimedBadge;
use He4rt\Character\Contracts\CharacterRepository;
use He4rt\Character\Tests\Unit\ProviderProviderTrait;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;

uses(BadgeProviderTrait::class, ProviderProviderTrait::class);

beforeEach(function (): void {
    // Repository stubs
    $this->characterRepositoryStub = Mockery::mock(CharacterRepository::class);
    $this->badgeRepositoryStub = Mockery::mock(BadgeRepository::class);

    // Action instances
    $this->persistClaimedBadge = new PersistClaimedBadge($this->characterRepositoryStub);
    $this->findBadgeBySlug = new FindBadgeBySlug($this->badgeRepositoryStub);

    // Action stubs
    $this->findExternalIdentityStub = Mockery::mock(FindExternalIdentity::class);
    $this->findCharacterIdByUserId = Mockery::mock(FindCharacterIdByUserId::class);

    // Sample entities
    $this->providerEntity = $this->validProviderEntity();
    $this->badgeEntity = $this->validBadgeEntity();
});

afterEach(function (): void {
    Mockery::close();
});

test('claim character badge success', function (): void {
    $this->findExternalIdentityStub
        ->shouldReceive('handle')
        ->with('canhassi-provider', 'canhassi-id')
        ->once()
        ->andReturn($this->providerEntity);

    $this->findCharacterIdByUserId
        ->shouldReceive('handle')
        ->with($this->providerEntity->model_id)
        ->once()
        ->andReturn('character-id');

    $this->badgeRepositoryStub
        ->shouldReceive('findBySlug')
        ->with('é o canhas')
        ->once()
        ->andReturn($this->badgeEntity);

    $this->characterRepositoryStub
        ->shouldReceive('claimBadge')
        ->with('character-id', $this->badgeEntity->id)
        ->once();

    $test = new ClaimCharacterBadge(
        $this->persistClaimedBadge,
        $this->findExternalIdentityStub,
        $this->findCharacterIdByUserId,
        $this->findBadgeBySlug
    );

    $test->handle('canhassi-provider', 'canhassi-id', 'é o canhas');
});
