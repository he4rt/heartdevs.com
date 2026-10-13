<?php

declare(strict_types=1);

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use He4rt\Streaming\Enums\StreamerStatus;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Events\StreamerActivated;
use He4rt\Streaming\Streamer\Events\StreamerDisabled;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([StreamerActivated::class, StreamerDisabled::class]);
});

test('o primeiro acesso cria um streamer ativo com token', function (): void {
    $user = User::factory()->streamer()->create();

    $streamer = resolve(EnsureStreamer::class)->handle($user);

    expect($streamer->status)->toBe(StreamerStatus::Active)
        ->and($streamer->overlay_token)->toMatch('/^[a-z0-9]{32}$/')
        ->and($streamer->overlay_token_hash)->toBe(hash('sha256', $streamer->overlay_token));
});

test('o segundo acesso reaproveita o registro e o token', function (): void {
    $user = User::factory()->streamer()->create();
    $first = resolve(EnsureStreamer::class)->handle($user);

    $second = resolve(EnsureStreamer::class)->handle($user);

    expect($second->is($first))->toBeTrue()
        ->and($second->overlay_token)->toBe($first->overlay_token)
        ->and(Streamer::query()->count())->toBe(1);
});

test('um streamer apagado volta no próximo acesso', function (): void {
    $streamer = Streamer::factory()->create();
    $streamer->delete();

    $restored = resolve(EnsureStreamer::class)->handle($streamer->user);

    expect($restored->is($streamer))->toBeTrue()
        ->and($restored->trashed())->toBeFalse();
});

test('perder a role desativa o streamer uma vez', function (): void {
    $streamer = Streamer::factory()->create();

    $streamer->user->removeRole(UserRole::Streamer);

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Disabled);
    Event::assertDispatchedTimes(StreamerDisabled::class, 1);
});

test('ganhar a role de novo reativa o streamer com o mesmo token', function (): void {
    $streamer = Streamer::factory()->disabled()->create();
    $streamer->user->removeRole(UserRole::Streamer);

    $token = $streamer->overlay_token;

    $streamer->user->assignRole(UserRole::Streamer);

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Active)
        ->and($streamer->overlay_token)->toBe($token);
    Event::assertDispatchedTimes(StreamerActivated::class, 1);
    Event::assertNotDispatched(StreamerDisabled::class);
});

test('o super-admin sem a role não perde o streamer quando as roles mudam', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $streamer = resolve(EnsureStreamer::class)->handle($admin);

    $admin->assignRole(UserRole::Streamer);
    $admin->removeRole(UserRole::Streamer);

    expect($streamer->refresh()->status)->toBe(StreamerStatus::Active);
    Event::assertNotDispatched(StreamerDisabled::class);
});

test('perder a role sem nunca ter aberto a Minha Live não cria streamer', function (): void {
    $user = User::factory()->streamer()->create();

    $user->removeRole(UserRole::Streamer);

    expect(Streamer::query()->exists())->toBeFalse();
});
