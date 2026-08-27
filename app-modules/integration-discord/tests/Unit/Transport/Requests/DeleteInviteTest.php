<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\Requests\Invites\DeleteInvite;
use Saloon\Enums\Method;

it('uses DELETE method', function (): void {
    $request = new DeleteInvite('hNQoYb9');

    expect($request->getMethod())->toBe(Method::DELETE);
});

it('resolves the correct endpoint', function (): void {
    $request = new DeleteInvite('hNQoYb9');

    expect($request->resolveEndpoint())->toBe('/invites/hNQoYb9');
});
