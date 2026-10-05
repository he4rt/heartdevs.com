<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Transport\Requests\Chat;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetGlobalChatBadges extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/chat/badges/global';
    }
}
