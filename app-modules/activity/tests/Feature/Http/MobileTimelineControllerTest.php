<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

it('rejects unauthenticated requests', function (): void {
    $this->getJson(route('mobile.timeline.index'))
        ->assertUnauthorized();
});

it('lists root posts, newest first, with counts', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $older = Timeline::factory()->for($user)->create(['created_at' => now()->subDay()]);
    $newer = Timeline::factory()->for($user)->create(['created_at' => now()]);
    Timeline::factory()->for($user)->create(['parent_id' => $newer->id, 'root_id' => $newer->id]);

    $response = $this->getJson(route('mobile.timeline.index'), ['Authorization' => "Bearer {$token}"])
        ->assertOk();

    $response->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.replies_count', 1)
        ->assertJsonPath('data.1.id', $older->id);
});

it('creates a post', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $this->postJson(route('mobile.timeline.store'), ['content' => 'Olá, comunidade He4rt!'], ['Authorization' => "Bearer {$token}"])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Olá, comunidade He4rt!')
        ->assertJsonPath('data.author.id', $user->id)
        ->assertJsonPath('data.parent_id', null)
        ->assertJsonPath('data.pinned', false);

    $this->assertDatabaseCount('activity_timeline', 1);
});

it('creates a post with images', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $response = $this->postJson(route('mobile.timeline.store'), [
        'content' => 'Com imagem',
        'images' => [UploadedFile::fake()->image('photo.jpg')],
    ], ['Authorization' => "Bearer {$token}"])
        ->assertCreated();

    expect($response->json('data.images'))->toHaveCount(1);
});

it('rejects an empty post', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);

    $this->postJson(route('mobile.timeline.store'), ['content' => ''], ['Authorization' => "Bearer {$token}"])
        ->assertUnprocessable();
});

it('creates a reply pinned to the root post', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $post = Timeline::factory()->create();

    $this->postJson(route('mobile.timeline.replies.store', ['post' => $post->getKey()]), ['content' => 'Concordo!'], ['Authorization' => "Bearer {$token}"])
        ->assertCreated()
        ->assertJsonPath('data.content', 'Concordo!')
        ->assertJsonPath('data.root_id', $post->id)
        ->assertJsonPath('data.parent_id', $post->id);
});

it('deletes own reply', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $post = Timeline::factory()->create();
    $reply = Timeline::factory()->for($user)->create(['parent_id' => $post->id, 'root_id' => $post->id]);

    $this->deleteJson(route('mobile.timeline.replies.destroy', ['reply' => $reply->getKey()]), [], ['Authorization' => "Bearer {$token}"])
        ->assertNoContent();

    $this->assertDatabaseMissing('activity_timeline', ['id' => $reply->id]);
});

it('rejects deleting a reply from another user', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $token = Auth::guard('api')->login($other);
    $post = Timeline::factory()->create();
    $reply = Timeline::factory()->for($owner)->create(['parent_id' => $post->id, 'root_id' => $post->id]);

    $this->deleteJson(route('mobile.timeline.replies.destroy', ['reply' => $reply->getKey()]), [], ['Authorization' => "Bearer {$token}"])
        ->assertForbidden();
});

it('rejects deleting a root post as if it were a reply', function (): void {
    $user = User::factory()->create();
    $token = Auth::guard('api')->login($user);
    $post = Timeline::factory()->for($user)->create();

    $this->deleteJson(route('mobile.timeline.replies.destroy', ['reply' => $post->getKey()]), [], ['Authorization' => "Bearer {$token}"])
        ->assertForbidden();
});
