<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('cria uma linha válida via factory com reaction como enum', function (): void {
    UserReaction::factory()->create();

    $reaction = UserReaction::query()->first();

    expect($reaction)->not->toBeNull()
        ->and($reaction->reaction)->toBeInstanceOf(TimelineReaction::class);

    $this->assertDatabaseCount('activity_user_reactions', 1);
});

it('pertence ao usuário que reagiu', function (): void {
    $user = User::factory()->create();
    $reaction = UserReaction::factory()->for($user)->create();

    expect($reaction->user)->toBeInstanceOf(User::class)
        ->and($reaction->user->id)->toBe($user->id);
});

it('devolve as reações do post com os usuários donos', function (): void {
    $timeline = Timeline::factory()->create();
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        UserReaction::factory()
            ->for($timeline)
            ->for($user)
            ->create();
    }

    $owners = $timeline->userReactions()->with('user')->get()->map(
        fn (UserReaction $reaction): string => $reaction->user->id,
    );

    expect($timeline->userReactions)->toHaveCount(3)
        ->and($owners->all())->toEqualCanonicalizing($users->pluck('id')->all());
});

it('rejeita segunda reação do mesmo usuário no mesmo post', function (): void {
    $user = User::factory()->create();
    $timeline = Timeline::factory()->create();

    UserReaction::factory()->for($user)->for($timeline)->create();

    expect(fn () => DB::transaction(
        fn () => UserReaction::factory()->for($user)->for($timeline)->create(),
    ))->toThrow(QueryException::class);

    $this->assertDatabaseCount('activity_user_reactions', 1);
});

it('rejeita reaction fora do conjunto do enum', function (): void {
    expect(fn () => UserReaction::factory()->create(['reaction' => 'xpto']))
        ->toThrow(ValueError::class);
});
