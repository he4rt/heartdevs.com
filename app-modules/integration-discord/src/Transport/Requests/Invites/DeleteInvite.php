<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport\Requests\Invites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class DeleteInvite extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        private readonly string $inviteCode,
    ) {}

    public function resolveEndpoint(): string
    {
        return sprintf('/invites/%s', $this->inviteCode);
    }
}
