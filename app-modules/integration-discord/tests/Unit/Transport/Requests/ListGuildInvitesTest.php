<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\Requests\Invites\ListGuildInvites;
use Saloon\Enums\Method;

it('uses GET method', function (): void {
    $request = new ListGuildInvites('guild-123');

    expect($request->getMethod())->toBe(Method::GET);
});

it('resolves the correct endpoint', function (): void {
    $request = new ListGuildInvites('guild-123');

    expect($request->resolveEndpoint())->toBe('/guilds/guild-123/invites');
});
