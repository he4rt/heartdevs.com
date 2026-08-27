<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\Requests\Members\RemoveMember;
use Saloon\Enums\Method;

it('uses DELETE method', function (): void {
    $request = new RemoveMember('123456', '789012');

    expect($request->getMethod())->toBe(Method::DELETE);
});

it('resolves the correct endpoint', function (): void {
    $request = new RemoveMember('123456', '789012');

    expect($request->resolveEndpoint())->toBe('/guilds/123456/members/789012');
});
