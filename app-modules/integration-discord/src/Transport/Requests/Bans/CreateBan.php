<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Bans;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class CreateBan extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        private readonly string $guildId,
        private readonly string $userId,
        private readonly int $deleteMessageSeconds = 0,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/bans/%s', $this->guildId, $this->userId);
    }

    /**
     * @return array<string, int>
     */
    protected function defaultBody(): array
    {
        return [
            'delete_message_seconds' => $this->deleteMessageSeconds,
        ];
    }
}
