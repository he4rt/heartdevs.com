<?php

declare(strict_types=1);

use He4rt\BotDiscord\Actions\Welcome\SendWelcomeDmAction;
use He4rt\BotDiscord\Enums\DiscordErrorCode;
use He4rt\BotDiscord\Welcome\WelcomeEmbedBuilder;

it('skips the public fallback only when the member left the guild', function (?DiscordErrorCode $errorCode, bool $expected): void {
    $action = new SendWelcomeDmAction(new WelcomeEmbedBuilder);

    expect($action->shouldAnnounceFallback($errorCode))->toBe($expected);
})->with([
    'dm closed' => [DiscordErrorCode::CannotSendMessagesToUser, true],
    'no mutual guilds' => [DiscordErrorCode::NoMutualGuilds, false],
    'unknown code' => [null, true],
]);
