<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Messages;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class CreateMessage extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $channelId,
        private readonly array $payload,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/channels/%s/messages', $this->channelId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }
}
