<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Members;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class RemoveMember extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $guildId,
        private readonly string $userId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/members/%s', $this->guildId, $this->userId);
    }
}
