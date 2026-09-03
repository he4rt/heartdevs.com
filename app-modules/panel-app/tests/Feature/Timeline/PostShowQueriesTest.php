<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\PostShow;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());

    Filament::setCurrentPanel(Filament::getPanel('app'));
});

function rootPost(): Timeline
{
    $entry = PostEntry::factory()->create(['content' => 'raiz']);

    return Timeline::factory()
        ->for(User::factory()->create(['name' => 'Autor Raiz']))
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $entry->id,
        ]);
}

function replyTo(Timeline $root, string $author): void
{
    $entry = PostEntry::factory()->create(['content' => 'resposta de '.$author]);

    Timeline::factory()
        ->for(User::factory()->create(['name' => $author]))
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $entry->id,
            'root_id' => $root->id,
            'parent_id' => $root->id,
        ]);
}

function mediaQueriesWhileRendering(Timeline $root): int
{
    $count = 0;

    DB::listen(function (QueryExecuted $query) use (&$count): void {
        if (str_contains($query->sql, 'media')) {
            $count++;
        }
    });

    livewire(PostShow::class, ['timelineId' => $root->id])->assertOk();

    return $count;
}

it('batches media instead of querying once per reply shown', function (): void {
    $root = rootPost();
    replyTo($root, 'Resposta Um');

    $comUmaResposta = mediaQueriesWhileRendering($root);

    replyTo($root, 'Resposta Dois');
    replyTo($root, 'Resposta Tres');

    expect(mediaQueriesWhileRendering($root))->toBe($comUmaResposta);
});
