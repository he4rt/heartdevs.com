<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\Actions\ReactWith;
use He4rt\Activity\Reaction\DTOs\ReactWithDTO;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->timeline = Timeline::factory()->create();
});

test('sem reação prévia cria a linha e retorna a reação escolhida', function (): void {
    $result = resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Love,
    ));

    expect($result)->toBe(TimelineReaction::Love);

    $this->assertDatabaseCount('activity_user_reactions', 1);
    $this->assertDatabaseHas('activity_user_reactions', [
        'user_id' => $this->user->id,
        'timeline_id' => $this->timeline->id,
        'reaction' => TimelineReaction::Love->value,
    ]);
});

test('mesma reação de novo remove a linha e retorna nulo', function (): void {
    UserReaction::factory()
        ->for($this->user)
        ->for($this->timeline)
        ->create(['reaction' => TimelineReaction::Like]);

    $result = resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Like,
    ));

    expect($result)->toBeNull();

    $this->assertDatabaseCount('activity_user_reactions', 0);
});

test('reação diferente atualiza a linha existente e retorna a nova', function (): void {
    $existing = UserReaction::factory()
        ->for($this->user)
        ->for($this->timeline)
        ->create(['reaction' => TimelineReaction::Like]);

    $result = resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Laugh,
    ));

    expect($result)->toBe(TimelineReaction::Laugh);

    $this->assertDatabaseCount('activity_user_reactions', 1);
    $this->assertDatabaseHas('activity_user_reactions', [
        'id' => $existing->id,
        'user_id' => $this->user->id,
        'timeline_id' => $this->timeline->id,
        'reaction' => TimelineReaction::Laugh->value,
    ]);
});

test('nunca existem 2 linhas para o mesmo par usuário e timeline', function (): void {
    $action = resolve(ReactWith::class);

    $action->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Like,
    ));

    $this->assertDatabaseCount('activity_user_reactions', 1);

    $action->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Fire,
    ));

    $this->assertDatabaseCount('activity_user_reactions', 1);

    $action->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Fire,
    ));

    $this->assertDatabaseCount('activity_user_reactions', 0);
});

test('reação de um usuário não afeta a reação de outro usuário no mesmo timeline', function (): void {
    $otherUser = User::factory()->create();

    resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Like,
    ));

    resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $otherUser->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Fire,
    ));

    $this->assertDatabaseCount('activity_user_reactions', 2);
    $this->assertDatabaseHas('activity_user_reactions', [
        'user_id' => $this->user->id,
        'timeline_id' => $this->timeline->id,
        'reaction' => TimelineReaction::Like->value,
    ]);
    $this->assertDatabaseHas('activity_user_reactions', [
        'user_id' => $otherUser->id,
        'timeline_id' => $this->timeline->id,
        'reaction' => TimelineReaction::Fire->value,
    ]);
});

test('reação do usuário em um timeline não afeta a reação dele em outro timeline', function (): void {
    $otherTimeline = Timeline::factory()->create();

    resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $this->timeline->id,
        reaction: TimelineReaction::Like,
    ));

    resolve(ReactWith::class)->handle(new ReactWithDTO(
        userId: $this->user->id,
        timelineId: $otherTimeline->id,
        reaction: TimelineReaction::Fire,
    ));

    $this->assertDatabaseCount('activity_user_reactions', 2);
    $this->assertDatabaseHas('activity_user_reactions', [
        'user_id' => $this->user->id,
        'timeline_id' => $this->timeline->id,
        'reaction' => TimelineReaction::Like->value,
    ]);
    $this->assertDatabaseHas('activity_user_reactions', [
        'user_id' => $this->user->id,
        'timeline_id' => $otherTimeline->id,
        'reaction' => TimelineReaction::Fire->value,
    ]);
});
