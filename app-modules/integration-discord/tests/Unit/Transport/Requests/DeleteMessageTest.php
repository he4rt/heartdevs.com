<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\Requests\Messages\DeleteMessage;
use Saloon\Enums\Method;

it('uses DELETE method', function (): void {
    $request = new DeleteMessage('channel-123', 'message-456');

    expect($request->getMethod())->toBe(Method::DELETE);
});

it('resolves the correct endpoint', function (): void {
    $request = new DeleteMessage('channel-123', 'message-456');

    expect($request->resolveEndpoint())->toBe('/channels/channel-123/messages/message-456');
});
