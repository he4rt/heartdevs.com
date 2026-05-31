<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Members;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListGuildMembers extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $guildId,
        private readonly int $limit = 1000,
        private readonly ?string $after = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/guilds/%s/members', $this->guildId);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['limit' => $this->limit];

        if ($this->after !== null) {
            $query['after'] = $this->after;
        }

        return $query;
    }
}
