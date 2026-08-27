<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Messages;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class DeleteMessage extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $channelId,
        private readonly string $messageId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/channels/%s/messages/%s', $this->channelId, $this->messageId);
    }
}
