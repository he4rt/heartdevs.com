<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Channels;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

final class CreateDmChannel extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $recipientId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/users/@me/channels';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'recipient_id' => $this->recipientId,
        ];
    }
}
