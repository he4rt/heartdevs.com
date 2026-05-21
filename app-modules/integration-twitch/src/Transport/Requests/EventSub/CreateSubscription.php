<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\EventSub;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class CreateSubscription extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, string>  $condition
     */
    public function __construct(
        private readonly string $type,
        private readonly string $version,
        private readonly array $condition,
        private readonly string $callbackUrl,
        private readonly string $secret,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/eventsub/subscriptions';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'type' => $this->type,
            'version' => $this->version,
            'condition' => $this->condition,
            'transport' => [
                'method' => 'webhook',
                'callback' => $this->callbackUrl,
                'secret' => $this->secret,
            ],
        ];
    }
}
