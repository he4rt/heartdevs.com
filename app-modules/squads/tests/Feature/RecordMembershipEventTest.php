<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Actions\RecordMembershipEvent;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMembershipEvent;

test('records a membership transition with action, roles, actor and occurred_at', function (): void {
    $tenant = Tenant::factory()->create();
    $squad = Squad::factory()->recycle($tenant)->create();
    $subject = User::factory()->create();
    $actor = User::factory()->create();
    $occurredAt = CarbonImmutable::parse('2026-07-09 12:00:00');

    $event = resolve(RecordMembershipEvent::class)->handle(
        squad: $squad,
        subject: $subject,
        action: MembershipAction::Promote,
        fromRole: SquadRole::Member,
        toRole: SquadRole::SubCaptain,
        actor: $actor,
        reason: 'Elected off-system.',
        occurredAt: $occurredAt,
    );

    expect($event)->toBeInstanceOf(SquadMembershipEvent::class)
        ->and($event->tenant_id)->toBe($squad->tenant_id)
        ->and($event->squad_id)->toBe($squad->id)
        ->and($event->user_id)->toBe($subject->id)
        ->and($event->actor_id)->toBe($actor->id)
        ->and($event->action)->toBe(MembershipAction::Promote)
        ->and($event->from_role)->toBe(SquadRole::Member)
        ->and($event->to_role)->toBe(SquadRole::SubCaptain)
        ->and($event->reason)->toBe('Elected off-system.')
        ->and($event->occurred_at->equalTo($occurredAt))->toBeTrue();

    $this->assertDatabaseHas('squad_membership_events', [
        'squad_id' => $squad->id,
        'user_id' => $subject->id,
        'actor_id' => $actor->id,
        'action' => 'promote',
        'from_role' => 'member',
        'to_role' => 'sub_captain',
    ]);
});

test('records a system event when there is no actor', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    $event = resolve(RecordMembershipEvent::class)->handle(
        squad: $squad,
        subject: $subject,
        action: MembershipAction::Join,
        toRole: SquadRole::Member,
    );

    expect($event->actor_id)->toBeNull()
        ->and($event->from_role)->toBeNull()
        ->and($event->to_role)->toBe(SquadRole::Member)
        ->and($event->metadata)->toBeNull()
        ->and($event->occurred_at)->not->toBeNull();

    $this->assertDatabaseHas('squad_membership_events', [
        'id' => $event->id,
        'actor_id' => null,
        'action' => 'join',
    ]);
});

test('stores structured metadata as jsonb', function (): void {
    $squad = Squad::factory()->create();
    $subject = User::factory()->create();

    $event = resolve(RecordMembershipEvent::class)->handle(
        squad: $squad,
        subject: $subject,
        action: MembershipAction::Leave,
        fromRole: SquadRole::Member,
        toRole: SquadRole::ExMember,
        metadata: ['source' => 'discord', 'removed_by_head' => true],
    );

    expect($event->fresh()->metadata)
        ->toBe(['source' => 'discord', 'removed_by_head' => true]);
});
