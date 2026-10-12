<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\Feed;
use He4rt\PanelApp\Livewire\Timeline\PostShow;
use He4rt\PanelApp\Livewire\Timeline\Reactions;
use He4rt\PanelApp\Pages\TimelinePage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::App->value));

    $this->user = User::factory()->create();

    $entry = PostEntry::factory()->create(['content' => 'End to end reaction post']);
    $this->post = Timeline::factory()->for(User::factory())->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry->id,
    ]);
});

/**
 * Re-renders the post exactly as the feed hands it to PostShow and returns its reaction state.
 *
 * @return array{counts: array<string, int>, mine: ?string}
 */
function reloadFeedReactionState(Timeline $post): array
{
    $feed = livewire(Feed::class)->assertSee('End to end reaction post');

    $summary = $feed->viewData('reactionSummaries')->get($post->id);

    $postShow = livewire(PostShow::class, ['timelineId' => $post->id, 'showReplies' => false]);

    expect($postShow->viewData('rootReactionCounts'))->toBe($summary?->counts ?? [])
        ->and($postShow->viewData('rootReactionMine'))->toBe($summary?->mine?->value);

    return ['counts' => $summary?->counts ?? [], 'mine' => $summary?->mine?->value];
}

function myReactionsOn(Timeline $post, User $user): int
{
    return UserReaction::query()
        ->where('user_id', $user->id)
        ->where('timeline_id', $post->id)
        ->count();
}

test('feed page shows the six available reactions with icon and label', function (): void {
    $this->actingAs($this->user);

    $response = $this->get(TimelinePage::getUrl())
        ->assertSuccessful()
        ->assertSee('End to end reaction post');

    foreach (TimelineReaction::cases() as $reaction) {
        $response
            ->assertSee($reaction->getLabel())
            ->assertSee($reaction->getEmoji(), escape: false);
    }
});

test('full flow through the feed: react, replace, toggle off and persist', function (): void {
    $this->actingAs($this->user);

    $state = reloadFeedReactionState($this->post);
    expect($state)->toBe(['counts' => [], 'mine' => null]);

    $reactions = livewire(Reactions::class, [
        'timelineId' => $this->post->id,
        'counts' => $state['counts'],
        'mine' => $state['mine'],
    ]);

    // Registrar "Amei"
    $reactions->call('reactWith', TimelineReaction::Love->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('counts', [TimelineReaction::Love->value => 1])
        ->assertSet('mine', TimelineReaction::Love->value)
        ->assertSee('font-semibold text-primary-500', escape: false);

    expect(reloadFeedReactionState($this->post))->toBe([
        'counts' => [TimelineReaction::Love->value => 1],
        'mine' => TimelineReaction::Love->value,
    ]);

    // Substituir por "Haha"
    $reactions->call('reactWith', TimelineReaction::Laugh->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('counts', [TimelineReaction::Laugh->value => 1])
        ->assertSet('mine', TimelineReaction::Laugh->value);

    expect(myReactionsOn($this->post, $this->user))->toBe(1)
        ->and(reloadFeedReactionState($this->post))->toBe([
            'counts' => [TimelineReaction::Laugh->value => 1],
            'mine' => TimelineReaction::Laugh->value,
        ]);

    // Toggle off em "Haha"
    $reactions->call('reactWith', TimelineReaction::Laugh->value)
        ->assertDispatched('timeline.reaction-updated')
        ->assertSet('counts', [])
        ->assertSet('mine', null);

    expect(myReactionsOn($this->post, $this->user))->toBe(0)
        ->and(reloadFeedReactionState($this->post))->toBe(['counts' => [], 'mine' => null]);

    // Reação final persiste após recarregar o feed
    $reactions->call('reactWith', TimelineReaction::Fire->value);

    $this->get(TimelinePage::getUrl())
        ->assertSuccessful()
        ->assertSee('font-semibold text-primary-500', escape: false);

    expect(reloadFeedReactionState($this->post))->toBe([
        'counts' => [TimelineReaction::Fire->value => 1],
        'mine' => TimelineReaction::Fire->value,
    ]);
});

test('replacing keeps other users reactions and decrements only the previous type', function (): void {
    $this->actingAs($this->user);

    UserReaction::factory()->for($this->post)->for(User::factory())->create([
        'reaction' => TimelineReaction::Like,
    ]);
    UserReaction::factory()->for($this->post)->for($this->user)->create([
        'reaction' => TimelineReaction::Like,
    ]);

    $state = reloadFeedReactionState($this->post);
    expect($state['counts'])->toBe([TimelineReaction::Like->value => 2]);

    livewire(Reactions::class, ['timelineId' => $this->post->id, ...$state])
        ->call('reactWith', TimelineReaction::Laugh->value);

    expect(reloadFeedReactionState($this->post)['counts'])
        ->toEqualCanonicalizing([
            TimelineReaction::Like->value => 1,
            TimelineReaction::Laugh->value => 1,
        ]);
});

test('a reaction outside the closed set is rejected end to end', function (): void {
    $this->actingAs($this->user);

    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->call('reactWith', 'xpto')
        ->assertNotDispatched('timeline.reaction-updated');

    expect(UserReaction::query()->where('timeline_id', $this->post->id)->exists())->toBeFalse()
        ->and(reloadFeedReactionState($this->post))->toBe(['counts' => [], 'mine' => null]);
});

test('guest is sent to authentication and nothing is persisted', function (): void {
    $this->get(TimelinePage::getUrl())
        ->assertRedirect(Filament::getPanel(FilamentPanel::App->value)->getLoginUrl());

    livewire(Reactions::class, ['timelineId' => $this->post->id])
        ->call('reactWith', TimelineReaction::Love->value)
        ->assertNotDispatched('timeline.reaction-updated');

    expect(UserReaction::query()->where('timeline_id', $this->post->id)->exists())->toBeFalse();
});
