<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Timeline\ReplyComposer;
use He4rt\PanelApp\Livewire\Timeline\ThreadReplies;
use He4rt\PanelApp\Pages\ThreadPage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $postEntry = PostEntry::factory()->create(['content' => 'Root post content']);
    $this->rootPost = Timeline::factory()
        ->for($this->user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);
});

test('thread page renders successfully', function (): void {
    $this->get(ThreadPage::getUrl(['record' => $this->rootPost->id]))
        ->assertSuccessful();
});

test('reply composer submits a reply', function (): void {
    livewire(ReplyComposer::class, ['timelineId' => $this->rootPost->id])
        ->fillForm(['content' => 'This is a test reply'])
        ->call('reply')
        ->assertDispatched('timeline.reply-created');

    $reply = Timeline::query()
        ->where('root_id', $this->rootPost->id)
        ->whereNotNull('parent_id')
        ->first();

    expect($reply)->not->toBeNull()
        ->and($reply->user_id)->toBe($this->user->id)
        ->and($reply->postable->content)->toBe('This is a test reply');
});

test('thread replies shows all replies in chronological order', function (): void {
    $replier = User::factory()->create(['name' => 'Replier One']);

    $entry1 = PostEntry::factory()->create(['content' => 'First reply']);
    Timeline::factory()->for($replier)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry1->id,
        'root_id' => $this->rootPost->id,
        'parent_id' => $this->rootPost->id,
        'created_at' => now()->subMinutes(10),
    ]);

    $entry2 = PostEntry::factory()->create(['content' => 'Second reply']);
    Timeline::factory()->for($this->user)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $entry2->id,
        'root_id' => $this->rootPost->id,
        'parent_id' => $this->rootPost->id,
        'created_at' => now()->subMinutes(5),
    ]);

    livewire(ThreadReplies::class, ['timelineId' => $this->rootPost->id])
        ->assertSee('First reply')
        ->assertSee('Second reply')
        ->assertSeeInOrder(['First reply', 'Second reply']);
});

test('owner can delete their own reply', function (): void {
    $replyEntry = PostEntry::factory()->create(['content' => 'My reply']);
    $reply = Timeline::factory()->for($this->user)->create([
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $replyEntry->id,
        'root_id' => $this->rootPost->id,
        'parent_id' => $this->rootPost->id,
    ]);

    livewire(ThreadReplies::class, ['timelineId' => $this->rootPost->id])
        ->call('deleteReply', $reply->id)
        ->assertDispatched('timeline.reply-deleted');

    expect(Timeline::query()->find($reply->id))->toBeNull();
});
