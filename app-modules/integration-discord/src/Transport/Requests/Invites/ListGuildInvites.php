<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Invites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListGuildInvites extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $guildId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/invites', $this->guildId);
    }
}
