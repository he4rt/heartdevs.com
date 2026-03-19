<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Tracking\Actions\TrackActivity;
use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;
use He4rt\Activity\Tracking\Events\InteractionTracked;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('tracks high tier activity as pending without crediting economy', function (): void {
    Event::fake([InteractionTracked::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create();

    $dto = new TrackActivityDTO(
        characterId: $character->id,
        tenantId: $tenant->id,
        type: ActivityType::Article,
        provider: IdentityProvider::DevTo,
        occurredAt: CarbonImmutable::now(),
        externalRef: 'devto:article:123',
    );

    $interaction = resolve(TrackActivity::class)->handle($dto);

    expect($interaction)->not->toBeNull()
        ->and($interaction->status)->toBe(ActivityStatus::Pending)
        ->and($interaction->value_tier)->toBe(ValueTier::High)
        ->and($interaction->coins_min)->toBe(100)
        ->and($interaction->coins_max)->toBe(300)
        ->and($interaction->coins_awarded)->toBeNull();

    Event::assertDispatched(InteractionTracked::class);
});

test('tracks low tier activity as auto approved and credits economy', function (): void {
    Event::fake([InteractionTracked::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create();

    $dto = new TrackActivityDTO(
        characterId: $character->id,
        tenantId: $tenant->id,
        type: ActivityType::Engagement,
        provider: IdentityProvider::DevTo,
        occurredAt: CarbonImmutable::now(),
    );

    $interaction = resolve(TrackActivity::class)->handle($dto);

    expect($interaction)->not->toBeNull()
        ->and($interaction->status)->toBe(ActivityStatus::AutoApproved)
        ->and($interaction->value_tier)->toBe(ValueTier::Low)
        ->and($interaction->coins_awarded)->toBe(1);

    $wallet = $character->fresh()->wallets()->first();
    expect($wallet)->not->toBeNull()
        ->and($wallet->balance)->toBe(1);

    Event::assertDispatched(InteractionTracked::class);
});

test('deduplicates by external ref', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create();

    $dto = new TrackActivityDTO(
        characterId: $character->id,
        tenantId: $tenant->id,
        type: ActivityType::Article,
        provider: IdentityProvider::DevTo,
        occurredAt: CarbonImmutable::now(),
        externalRef: 'devto:article:456',
    );

    $first = resolve(TrackActivity::class)->handle($dto);
    $second = resolve(TrackActivity::class)->handle($dto);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();
});
