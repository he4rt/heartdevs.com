<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Roles;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListGuildRoles extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $guildId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/roles', $this->guildId);
    }
}
