<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\EventSub;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class DeleteSubscription extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $subscriptionId,
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
        return [
            'id' => $this->subscriptionId,
        ];
    }
}
