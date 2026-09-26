<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\Reactions;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $postEntry = PostEntry::factory()->create();
    $this->post = Timeline::factory()->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $postEntry->id,
    ]);
});

test('shows the six available reactions with icon and label', function (): void {
    $this->actingAs($this->user);

    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->assertSee('Curtir')
        ->assertSee('Amei')
        ->assertSee('Haha')
        ->assertSee('Parabéns')
        ->assertSee('Fogo')
        ->assertSee('Triste');
});

test('registers a reaction, updates state and dispatches the update event', function (): void {
    $this->actingAs($this->user);

    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->call('reactWith', TimelineReaction::Love->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('mine', TimelineReaction::Love->value)
        ->assertSet('counts', [TimelineReaction::Love->value => 1]);

    expect(UserReaction::query()
        ->where('user_id', $this->user->id)
        ->where('timeline_id', $this->post->id)
        ->first()?->reaction)->toBe(TimelineReaction::Love);
});

test('replaces an existing reaction with another one', function (): void {
    $this->actingAs($this->user);

    UserReaction::factory()->create([
        'user_id' => $this->user->id,
        'timeline_id' => $this->post->id,
        'reaction' => TimelineReaction::Like,
    ]);

    livewire(Reactions::class, [
        'timelineId' => $this->post->id,
        'counts' => [TimelineReaction::Like->value => 1],
        'mine' => TimelineReaction::Like->value,
    ])
        ->call('reactWith', TimelineReaction::Laugh->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('mine', TimelineReaction::Laugh->value)
        ->assertSet('counts', [TimelineReaction::Laugh->value => 1]);

    expect(UserReaction::query()
        ->where('user_id', $this->user->id)
        ->where('timeline_id', $this->post->id)
        ->count())->toBe(1)
        ->and(UserReaction::query()->first()?->reaction)->toBe(TimelineReaction::Laugh);
});

test('toggles off the current reaction when chosen again', function (): void {
    $this->actingAs($this->user);

    UserReaction::factory()->create([
        'user_id' => $this->user->id,
        'timeline_id' => $this->post->id,
        'reaction' => TimelineReaction::Like,
    ]);

    livewire(Reactions::class, [
        'timelineId' => $this->post->id,
        'counts' => [TimelineReaction::Like->value => 1],
        'mine' => TimelineReaction::Like->value,
    ])
        ->call('reactWith', TimelineReaction::Like->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('mine', null)
        ->assertSet('counts', []);

    expect(UserReaction::query()
        ->where('user_id', $this->user->id)
        ->where('timeline_id', $this->post->id)
        ->exists())->toBeFalse();
});

test('rejects a reaction outside the closed set', function (): void {
    $this->actingAs($this->user);

    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->call('reactWith', 'xpto')
        ->assertNotDispatched('timeline.reaction-updated');

    expect(UserReaction::query()
        ->where('timeline_id', $this->post->id)
        ->exists())->toBeFalse();
});

test('blocks an unauthenticated user from reacting', function (): void {
    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->call('reactWith', TimelineReaction::Love->value)
        ->assertNotDispatched('timeline.reaction-updated');

    expect(UserReaction::query()
        ->where('timeline_id', $this->post->id)
        ->exists())->toBeFalse();
});
