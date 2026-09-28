<?php

declare(strict_types=1);

use He4rt\BotDiscord\Concerns\ResolvesGuildIcon;

beforeEach(function (): void {
    $this->resolver = new class
    {
        use ResolvesGuildIcon;
    };
});

test('builds a png cdn url for a static guild icon', function (): void {
    expect($this->resolver->guildIconUrl('540000000000000000', 'abc123'))
        ->toBe('https://cdn.discordapp.com/icons/540000000000000000/abc123.png');
});

test('builds a gif cdn url for an animated guild icon', function (): void {
    expect($this->resolver->guildIconUrl('540000000000000000', 'a_abc123'))
        ->toBe('https://cdn.discordapp.com/icons/540000000000000000/a_abc123.gif');
});

test('returns null when the guild has no icon', function (): void {
    expect($this->resolver->guildIconUrl('540000000000000000', iconHash: null))->toBeNull();
});

test('returns null when the guild id is missing', function (): void {
    expect($this->resolver->guildIconUrl('', 'abc123'))->toBeNull();
});
