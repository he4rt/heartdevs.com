<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\Chat;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetChannelChatBadges extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $broadcasterId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/chat/badges';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return [
            'broadcaster_id' => $this->broadcasterId,
        ];
    }
}
