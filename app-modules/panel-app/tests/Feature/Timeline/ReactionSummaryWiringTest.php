<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\Feed;
use He4rt\PanelApp\Livewire\Timeline\PostShow;
use He4rt\PanelApp\Livewire\Timeline\Reactions;
use He4rt\PanelApp\Livewire\Timeline\ThreadReplies;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

/**
 * @return list<Timeline>
 */
function seedReactedTimelinePosts(int $count, User $author, User $reactor): array
{
    $posts = [];

    for ($i = 0; $i < $count; $i++) {
        $entry = PostEntry::factory()->create(['content' => "Reacted post {$i}"]);
        $post = Timeline::factory()->for($author)->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $entry->id,
        ]);

        UserReaction::factory()->for($post)->for($reactor)->create([
            'reaction' => TimelineReaction::cases()[$i % count(TimelineReaction::cases())],
        ]);

        $posts[] = $post;
    }

    return $posts;
}

/**
 * @param  callable(): mixed  $callback
 * @return array{0: mixed, 1: list<array{query: string, bindings: array<mixed>, time: float}>}
 */
function withQueryLog(callable $callback): array
{
    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        return [$callback(), $connection->getQueryLog()];
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }
}

/** @param  list<array{query: string, bindings: array<mixed>, time: float}>  $queries */
function countUserReactionQueries(array $queries): int
{
    return collect($queries)
        ->filter(fn (array $query): bool => str_contains(mb_strtolower($query['query']), 'activity_user_reactions'))
        ->count();
}

/** @param  list<array{query: string, bindings: array<mixed>, time: float}>  $queries */
function hasLegacyReactionsCountQuery(array $queries): bool
{
    return collect($queries)->contains(
        fn (array $query): bool => str_contains(mb_strtolower($query['query']), 'reactions_count')
            || (str_contains(mb_strtolower($query['query']), 'activity_reactions')
                && str_contains(mb_strtolower($query['query']), 'select count'))
    );
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));
});

test('feed and thread show the same reaction breakdown and highlight', function (): void {
    $author = User::factory()->create();
    $other = User::factory()->create();

    $entry = PostEntry::factory()->create(['content' => 'Shared reaction post']);
    $post = Timeline::factory()->for($author)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry->id,
    ]);

    UserReaction::factory()->for($post)->for($this->user)->create([
        'reaction' => TimelineReaction::Fire,
    ]);
    UserReaction::factory()->for($post)->for($other)->create([
        'reaction' => TimelineReaction::Love,
    ]);

    $fireEmoji = TimelineReaction::Fire->getEmoji();
    $loveEmoji = TimelineReaction::Love->getEmoji();

    livewire(Feed::class)
        ->assertSee($fireEmoji, escape: false)
        ->assertSee($loveEmoji, escape: false)
        ->assertSee('1')
        ->assertSee('font-semibold text-primary-500', escape: false);

    livewire(PostShow::class, [
        'timelineId' => $post->id,
        'showReplies' => false,
    ])
        ->assertSee($fireEmoji, escape: false)
        ->assertSee($loveEmoji, escape: false)
        ->assertSee('1')
        ->assertSee('font-semibold text-primary-500', escape: false);
});

test('feed reaction query count stays constant as reacted posts grow', function (): void {
    $author = User::factory()->create();

    seedReactedTimelinePosts(5, $author, $this->user);

    [, $queriesForFive] = withQueryLog(function (): void {
        livewire(Feed::class)->call('loadMore');
    });

    $fiveCount = countUserReactionQueries($queriesForFive);

    seedReactedTimelinePosts(20, $author, $this->user);

    [, $queriesForTwentyFive] = withQueryLog(function (): void {
        livewire(Feed::class)->call('loadMore');
    });

    $twentyFiveCount = countUserReactionQueries($queriesForTwentyFive);

    expect($fiveCount)->toBeGreaterThan(0)
        ->and($twentyFiveCount)->toBe($fiveCount);
});

test('timeline feed path no longer uses withCount reactions', function (): void {
    $author = User::factory()->create();
    seedReactedTimelinePosts(3, $author, $this->user);

    [, $queries] = withQueryLog(function (): void {
        livewire(Feed::class);
    });

    expect(hasLegacyReactionsCountQuery($queries))->toBeFalse();
});

test('post show refreshes reaction state after timeline.reaction-updated', function (): void {
    $entry = PostEntry::factory()->create(['content' => 'Refreshable post']);
    $post = Timeline::factory()->for($this->user)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry->id,
    ]);

    $component = livewire(PostShow::class, [
        'timelineId' => $post->id,
        'showReplies' => false,
    ])->assertViewHas('rootReactionCounts', []);

    livewire(Reactions::class, ['timelineId' => $post->id])
        ->call('reactWith', TimelineReaction::Celebrate->value)
        ->assertDispatched('timeline.reaction-updated');

    $component
        ->dispatch('timeline.reaction-updated')
        ->assertViewHas('rootReactionCounts', [TimelineReaction::Celebrate->value => 1])
        ->assertViewHas('rootReactionMine', TimelineReaction::Celebrate->value);
});

test('thread replies receive batched reaction summaries for each reply', function (): void {
    $entry = PostEntry::factory()->create(['content' => 'Root for replies']);
    $root = Timeline::factory()->for($this->user)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry->id,
    ]);

    $replyEntry = PostEntry::factory()->create(['content' => 'Reply with fire']);
    $reply = Timeline::factory()->for($this->user)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $replyEntry->id,
        'root_id' => $root->id,
        'parent_id' => $root->id,
    ]);

    UserReaction::factory()->for($reply)->for($this->user)->create([
        'reaction' => TimelineReaction::Fire,
    ]);

    livewire(ThreadReplies::class, ['timelineId' => $root->id])
        ->assertSee('Reply with fire')
        ->assertSee(TimelineReaction::Fire->getEmoji(), escape: false)
        ->assertSee('1');
});
