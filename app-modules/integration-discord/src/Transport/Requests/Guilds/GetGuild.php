<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Guilds;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetGuild extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $guildId,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s', $this->guildId);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return ['with_counts' => 'true'];
    }
}
