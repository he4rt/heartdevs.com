<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Members;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class ModifyMember extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $guildId,
        private readonly string $userId,
        private readonly array $payload,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/members/%s', $this->guildId, $this->userId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }
}
