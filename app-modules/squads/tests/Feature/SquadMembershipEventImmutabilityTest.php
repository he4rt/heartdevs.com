<?php

declare(strict_types=1);

use He4rt\Squads\Exceptions\MembershipEventIsImmutable;
use He4rt\Squads\Models\SquadMembershipEvent;

test('a recorded membership event cannot be updated', function (): void {
    $event = SquadMembershipEvent::factory()->create();

    try {
        $event->update(['reason' => 'tampered']);
        $this->fail('Expected the membership event to be immutable.');
    } catch (MembershipEventIsImmutable) {
        // expected
    }

    expect($event->fresh()->reason)->toBeNull();
});

test('a recorded membership event cannot be deleted', function (): void {
    $event = SquadMembershipEvent::factory()->create();

    try {
        $event->delete();
        $this->fail('Expected the membership event to be immutable.');
    } catch (MembershipEventIsImmutable) {
        // expected
    }

    $this->assertDatabaseHas('squad_membership_events', [
        'id' => $event->id,
    ]);
});
