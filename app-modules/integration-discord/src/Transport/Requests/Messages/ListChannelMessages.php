<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Messages;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListChannelMessages extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $channelId,
        private readonly int $limit = 100,
        private readonly ?string $before = null,
        private readonly ?string $after = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/channels/%s/messages', $this->channelId);
    }

    /**
     * @return array<string, string|int>
     */
    protected function defaultQuery(): array
    {
        $query = ['limit' => $this->limit];

        if ($this->before !== null) {
            $query['before'] = $this->before;
        }

        if ($this->after !== null) {
            $query['after'] = $this->after;
        }

        return $query;
    }
}
