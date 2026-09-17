<?php

declare(strict_types=1);

use Discord\Http\Exceptions\NoPermissionsException;
use He4rt\BotDiscord\Enums\DiscordErrorCode;

it('maps each case to its discord json error code', function (): void {
    expect(DiscordErrorCode::CannotSendMessagesToUser->value)->toBe(50_007)
        ->and(DiscordErrorCode::NoMutualGuilds->value)->toBe(50_278);
});

it('resolves the error code from the exception', function (int $code, ?DiscordErrorCode $expected): void {
    expect(DiscordErrorCode::fromThrowable(new NoPermissionsException('Forbidden', $code)))->toBe($expected);
})->with([
    'cannot send messages to user' => [50_007, DiscordErrorCode::CannotSendMessagesToUser],
    'no mutual guilds' => [50_278, DiscordErrorCode::NoMutualGuilds],
    'unknown code' => [40_001, null],
]);

it('translates a distinct label and description for every error code', function (string $locale): void {
    app()->setLocale($locale);

    $cases = DiscordErrorCode::cases();

    $labels = array_map(fn (DiscordErrorCode $code): string => $code->getLabel(), $cases);
    $descriptions = array_map(fn (DiscordErrorCode $code): string => $code->getDescription(), $cases);

    expect([...$labels, ...$descriptions])->each->not->toStartWith('bot-discord::')
        ->and(array_unique($labels))->toHaveSameSize($cases)
        ->and(array_unique($descriptions))->toHaveSameSize($cases);
})->with(['en', 'pt_BR']);
