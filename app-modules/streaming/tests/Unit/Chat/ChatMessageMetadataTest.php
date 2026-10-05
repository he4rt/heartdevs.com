<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Streaming\Chat\Data\ChatBadge;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;

test('ida e volta preserva nome, cor, badges e fragmentos', function (): void {
    $metadata = new ChatMessageMetadata(
        displayName: 'MariaCoda',
        color: '#8b2fe8',
        badges: [new ChatBadge('subscriber', '12', 'https://cdn.example/sub.png')],
        fragments: [ChatFragment::text('boa noite '), ChatFragment::emote('Kappa', '25', 'https://cdn.example/25.png')],
    );

    $restored = ChatMessageMetadata::fromArray(json_decode(json_encode($metadata->toArray()), associative: true));

    expect($restored)->toEqual($metadata)
        ->and($restored->isDeleted())->toBeFalse();
});

test('badges e fragmentos quebrados são descartados', function (): void {
    $metadata = ChatMessageMetadata::fromArray([
        'display_name' => 'MariaCoda',
        'badges' => [['set_id' => 'vip'], 'lixo', ['set_id' => 'moderator', 'version' => '1']],
        'fragments' => [['kind' => 'emote', 'text' => 'Kappa'], ['text' => 42]],
    ]);

    expect($metadata->badges)->toEqual([new ChatBadge('moderator', '1')])
        ->and($metadata->fragments)->toEqual([ChatFragment::text('Kappa')]);
});

test('marcar como apagada guarda a data', function (): void {
    $deletedAt = CarbonImmutable::parse('2026-10-04 21:00:00');

    $metadata = new ChatMessageMetadata('MariaCoda')->withDeletedAt($deletedAt);
    $restored = ChatMessageMetadata::fromArray($metadata->toArray());

    expect($restored->isDeleted())->toBeTrue()
        ->and($restored->deletedAt?->equalTo($deletedAt))->toBeTrue();
});
