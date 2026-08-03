<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use Illuminate\Database\QueryException;

test('a squad member persists with a uuid id, role cast and timestamps', function (): void {
    $squad = Squad::factory()->create();
    $user = User::factory()->create();

    $member = SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $user->id,
        'role' => SquadRole::Captain,
    ]);

    $fresh = $member->fresh();

    expect($fresh->id)->toBe($member->id)
        ->and($fresh->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')
        ->and($fresh->role)->toBe(SquadRole::Captain)
        ->and($fresh->joined_at)->not->toBeNull()
        ->and($fresh->created_at)->not->toBeNull()
        ->and($fresh->left_at)->toBeNull();
});

test('the same user cannot hold two rows in the same squad', function (): void {
    $squad = Squad::factory()->create();
    $user = User::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $user->id,
    ]);

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'user_id' => $user->id,
    ]);
})->throws(QueryException::class);

test('a squad cannot hold two captains', function (): void {
    $squad = Squad::factory()->create();

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'role' => SquadRole::Captain,
    ]);

    SquadMember::factory()->create([
        'squad_id' => $squad->id,
        'role' => SquadRole::Captain,
    ]);
})->throws(QueryException::class);
