<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Policies\SquadPolicy;
use Illuminate\Auth\Access\AuthorizationException;

describe('SquadPolicy', static function (): void {
    test('a captain is authorized to manage their own squad', function (): void {
        $squad = Squad::factory()->create();
        $captain = User::factory()->create();

        SquadMember::factory()->create([
            'squad_id' => $squad->id,
            'user_id' => $captain->id,
            'role' => SquadRole::Captain,
        ]);

        $policy = resolve(SquadPolicy::class);

        expect($policy->canManage($captain, $squad))->toBeTrue();

        $policy->authorize($captain, $squad);
    });

    test('a captain is denied managing a squad they do not belong to', function (): void {
        $ownSquad = Squad::factory()->create();
        $otherSquad = Squad::factory()->create();
        $captain = User::factory()->create();

        SquadMember::factory()->create([
            'squad_id' => $ownSquad->id,
            'user_id' => $captain->id,
            'role' => SquadRole::Captain,
        ]);

        $policy = resolve(SquadPolicy::class);

        expect($policy->canManage($captain, $otherSquad))->toBeFalse();

        $policy->authorize($captain, $otherSquad);
    })->throws(AuthorizationException::class);

    test('a super-admin is authorized on any squad, even without membership', function (): void {
        config(['he4rt.admins' => 'guisaliba']);

        $squad = Squad::factory()->create();
        $admin = User::factory()->create([
            'username' => 'guisaliba',
        ]);

        $policy = resolve(SquadPolicy::class);

        expect($policy->canManage($admin, $squad))->toBeTrue();

        $policy->authorize($admin, $squad);
    });

    test('a sub-captain is authorized to manage their own squad', function (): void {
        $squad = Squad::factory()->create();
        $subCaptain = User::factory()->create();

        SquadMember::factory()->create([
            'squad_id' => $squad->id,
            'user_id' => $subCaptain->id,
            'role' => SquadRole::SubCaptain,
        ]);

        $policy = resolve(SquadPolicy::class);

        expect($policy->canManage($subCaptain, $squad))->toBeTrue();

        $policy->authorize($subCaptain, $squad);
    });

    test('a common member is denied managing their own squad', function (): void {
        $squad = Squad::factory()->create();
        $member = User::factory()->create();

        SquadMember::factory()->create([
            'squad_id' => $squad->id,
            'user_id' => $member->id,
            'role' => SquadRole::Member,
        ]);

        $policy = resolve(SquadPolicy::class);

        expect($policy->canManage($member, $squad))->toBeFalse();

        $policy->authorize($member, $squad);
    })->throws(AuthorizationException::class);
});
