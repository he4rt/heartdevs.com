<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Channels;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListGuildChannels extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $guildId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/channels', $this->guildId);
    }
}
