<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\EventSub;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class ListSubscriptions extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly ?string $status = null,
        private readonly ?string $type = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/eventsub/subscriptions';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->status !== null) {
            $query['status'] = $this->status;
        }

        if ($this->type !== null) {
            $query['type'] = $this->type;
        }

        return $query;
    }
}
