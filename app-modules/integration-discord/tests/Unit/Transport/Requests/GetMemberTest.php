<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\Requests\Members\GetMember;
use Saloon\Enums\Method;

it('uses GET method', function (): void {
    $request = new GetMember('123456', '789012');

    expect($request->getMethod())->toBe(Method::GET);
});

it('resolves the correct endpoint', function (): void {
    $request = new GetMember('123456', '789012');

    expect($request->resolveEndpoint())->toBe('/guilds/123456/members/789012');
});
