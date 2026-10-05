<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

test('um usuário tem um streamer só', function (): void {
    $streamer = Streamer::factory()->create();

    Streamer::factory()->create(['user_id' => $streamer->user_id]);
})->throws(UniqueConstraintViolationException::class);

test('o token fica criptografado no banco', function (): void {
    $token = 'abcdefghijklmnopqrstuvwxyz012345';
    $streamer = Streamer::factory()->withToken($token)->create();

    expect(DB::table('streamers')->where('id', $streamer->getKey())->value('overlay_token'))->not->toContain($token)
        ->and($streamer->fresh()?->overlay_token)->toBe($token)
        ->and($streamer->toArray())->not->toHaveKeys(['overlay_token', 'overlay_token_hash']);
});

test('apagar o usuário no banco não leva o streamer junto', function (): void {
    $streamer = Streamer::factory()->create();

    DB::table('users')->where('id', $streamer->user_id)->delete();
})->throws(QueryException::class);

test('uma identidade vira uma fonte só por streamer', function (): void {
    $source = StreamerSource::factory()->create();

    StreamerSource::factory()->create([
        'streamer_id' => $source->streamer_id,
        'external_identity_id' => $source->external_identity_id,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('as configurações gravam e voltam tipadas', function (): void {
    $streamer = Streamer::factory()->create();

    $streamer->update([
        'settings' => $streamer->settings
            ->withScene(new StartingSoonSettings(title: 'Live de PHP'))
            ->withAlerts($streamer->settings->alerts->with(StreamEventType::Follow, enabled: false)),
    ]);

    $settings = $streamer->fresh()?->settings;

    expect($settings?->startingSoon->title)->toBe('Live de PHP')
        ->and($settings?->alerts->isEnabled(StreamEventType::Follow))->toBeFalse();
});
