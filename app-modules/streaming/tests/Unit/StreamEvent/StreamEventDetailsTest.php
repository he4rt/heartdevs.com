<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;

test('o resumo descreve cada tipo de evento', function (StreamEventDetails $details, string $summary): void {
    expect($details->summary())->toBe($summary);
})->with([
    'sub de 3 meses' => [new SubDetails(months: 3), '3 meses'],
    'sub de 1 mês' => [new SubDetails(months: 1), '1 mês'],
    '5 gift subs' => [new GiftSubDetails(total: 5), '5 subs'],
    '1 gift sub' => [new GiftSubDetails(total: 1), '1 sub'],
    '500 bits' => [new CheerDetails(bits: 500), '500 bits'],
    '3250 bits' => [new CheerDetails(bits: 3_250), '3.250 bits'],
    'raid de 42' => [new RaidDetails(viewers: 42), '+42 viewers'],
]);

test('valores com o tipo errado viram o padrão', function (): void {
    $sub = SubDetails::fromArray(['tier' => 'ouro', 'months' => '3']);
    $cheer = CheerDetails::fromArray(['bits' => -10]);

    expect($sub->tier)->toBe(SubTier::Tier1)
        ->and($sub->months)->toBe(1)
        ->and($cheer->bits)->toBe(0);
});
