<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Enums\DiscordEventType;
use Illuminate\Support\Facades\Lang;

test('every DiscordEventType case has an en and pt_BR translation', function (): void {
    foreach (DiscordEventType::cases() as $case) {
        $key = 'integration-discord::enums.discord_event_type.'.$case->value;

        expect(Lang::has($key, 'en'))->toBeTrue('Missing en translation for '.$case->value)
            ->and(Lang::has($key, 'pt_BR'))->toBeTrue('Missing pt_BR translation for '.$case->value);
    }
});

test('every DiscordEventType case resolves a non-empty label', function (): void {
    foreach (DiscordEventType::cases() as $case) {
        expect($case->getLabel())->not->toBeEmpty()
            ->and($case->getLabel())->not->toContain('discord_event_type');
    }
});
