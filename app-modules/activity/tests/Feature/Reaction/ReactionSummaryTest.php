<?php

declare(strict_types=1);

use He4rt\Activity\Reaction\DTOs\TimelineReactionSummary;
use He4rt\Activity\Reaction\Enums\TimelineReaction;
use He4rt\Activity\Reaction\Models\UserReaction;
use He4rt\Activity\Reaction\Queries\ReactionSummary;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

/**
 * Cria $count posts e, em cada um, uma reação de cada usuário informado,
 * ciclando pelos cases do enum. Devolve a coleção de posts.
 *
 * @param  EloquentCollection<int, User>  $users
 * @return EloquentCollection<int, Timeline>
 */
function seedReactionSummaryPosts(int $count, EloquentCollection $users): EloquentCollection
{
    $posts = Timeline::factory()->count($count)->create();
    $cases = TimelineReaction::cases();

    foreach ($posts as $post) {
        foreach ($users->values() as $index => $user) {
            UserReaction::factory()
                ->for($post)
                ->for($user)
                ->create(['reaction' => $cases[$index % count($cases)]]);
        }
    }

    return $posts;
}

/**
 * Executa $callback com o query log ligado e devolve o resultado dele junto
 * com o número de consultas executadas.
 *
 * @template TResult
 *
 * @param  Closure(): TResult  $callback
 * @return array{0: TResult, 1: int}
 */
function reactionSummaryQueries(Closure $callback): array
{
    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        $result = $callback();

        return [$result, count($connection->getQueryLog())];
    } finally {
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }
}

beforeEach(function (): void {
    $this->me = User::factory()->create();
});

test('devolve o breakdown por post só com contagens positivas e a minha reação', function (): void {
    [$a, $b, $c] = Timeline::factory()->count(3)->create();
    [$alice, $bob, $carol] = User::factory()->count(3)->create();

    // Post A: 👍 x2 (eu + alice), ❤️ x1 (bob)
    UserReaction::factory()->for($a)->for($this->me)->create(['reaction' => TimelineReaction::Like]);
    UserReaction::factory()->for($a)->for($alice)->create(['reaction' => TimelineReaction::Like]);
    UserReaction::factory()->for($a)->for($bob)->create(['reaction' => TimelineReaction::Love]);

    // Post B: 🔥 x1 (carol) — eu não reagi
    UserReaction::factory()->for($b)->for($carol)->create(['reaction' => TimelineReaction::Fire]);

    // Post C: sem reações

    $summary = new ReactionSummary()->forTimelines([$a->id, $b->id, $c->id], $this->me->id);

    expect($summary)->toHaveCount(3)
        ->and($summary->keys()->all())->toEqualCanonicalizing([$a->id, $b->id, $c->id])
        ->and($summary->every(fn ($item): bool => $item instanceof TimelineReactionSummary))->toBeTrue();

    expect($summary[$a->id]->timelineId)->toBe($a->id)
        ->and($summary[$a->id]->counts)->toBe([
            TimelineReaction::Like->value => 2,
            TimelineReaction::Love->value => 1,
        ])
        ->and($summary[$a->id]->mine)->toBe(TimelineReaction::Like)
        ->and($summary[$a->id]->total())->toBe(3)
        ->and($summary[$a->id]->countOf(TimelineReaction::Like))->toBe(2)
        ->and($summary[$a->id]->countOf(TimelineReaction::Sad))->toBe(0);

    expect($summary[$b->id]->counts)->toBe([TimelineReaction::Fire->value => 1])
        ->and($summary[$b->id]->mine)->toBeNull();

    expect($summary[$c->id]->counts)->toBeEmpty()
        ->and($summary[$c->id]->mine)->toBeNull()
        ->and($summary[$c->id]->total())->toBe(0);
});

test('ignora reações de posts que não foram pedidos', function (): void {
    [$wanted, $other] = Timeline::factory()->count(2)->create();

    UserReaction::factory()->for($wanted)->for($this->me)->create(['reaction' => TimelineReaction::Laugh]);
    UserReaction::factory()->for($other)->for($this->me)->create(['reaction' => TimelineReaction::Sad]);

    $summary = new ReactionSummary()->forTimelines([$wanted->id], $this->me->id);

    expect($summary)->toHaveCount(1)
        ->and($summary->has($other->id))->toBeFalse()
        ->and($summary[$wanted->id]->counts)->toBe([TimelineReaction::Laugh->value => 1])
        ->and($summary[$wanted->id]->mine)->toBe(TimelineReaction::Laugh);
});

test('sem userId, mine é sempre null e a segunda consulta não roda', function (): void {
    $posts = seedReactionSummaryPosts(3, User::factory()->count(2)->create());

    [$summary, $queries] = reactionSummaryQueries(
        fn () => new ReactionSummary()->forTimelines($posts->pluck('id'), userId: null),
    );

    expect($queries)->toBe(1)
        ->and($summary)->toHaveCount(3)
        ->and($summary->pluck('mine')->filter())->toBeEmpty()
        ->and($summary->every(fn (TimelineReactionSummary $item): bool => $item->total() === 2))->toBeTrue();
});

test('com userId usa no máximo duas consultas', function (): void {
    $posts = seedReactionSummaryPosts(3, User::factory()->count(2)->create()->push($this->me));

    [$summary, $queries] = reactionSummaryQueries(
        fn () => new ReactionSummary()->forTimelines($posts->pluck('id'), $this->me->id),
    );

    expect($queries)->toBeLessThanOrEqual(2)
        ->and($summary->every(fn (TimelineReactionSummary $item): bool => $item->mine instanceof TimelineReaction))->toBeTrue();
});

test('o número de consultas não cresce com a quantidade de posts', function (): void {
    $users = User::factory()->count(4)->create()->push($this->me);

    $few = seedReactionSummaryPosts(3, $users);
    $many = seedReactionSummaryPosts(30, $users);

    [, $queriesForFew] = reactionSummaryQueries(fn () => new ReactionSummary()->forTimelines($few->pluck('id'), $this->me->id));
    [, $queriesForMany] = reactionSummaryQueries(fn () => new ReactionSummary()->forTimelines($many->pluck('id'), $this->me->id));

    expect($queriesForFew)->toBeLessThanOrEqual(2)
        ->and($queriesForMany)->toBe($queriesForFew);
});

test('lista vazia de ids devolve coleção vazia sem consultar o banco', function (): void {
    [$summary, $queries] = reactionSummaryQueries(
        fn () => new ReactionSummary()->forTimelines([], $this->me->id),
    );

    expect($queries)->toBe(0)
        ->and($summary)->toBeEmpty();
});

test('ids repetidos são consolidados num único item', function (): void {
    $post = Timeline::factory()->create();
    UserReaction::factory()->for($post)->for($this->me)->create(['reaction' => TimelineReaction::Celebrate]);

    $summary = new ReactionSummary()->forTimelines([$post->id, $post->id], $this->me->id);

    expect($summary)->toHaveCount(1)
        ->and($summary[$post->id]->counts)->toBe([TimelineReaction::Celebrate->value => 1]);
});
