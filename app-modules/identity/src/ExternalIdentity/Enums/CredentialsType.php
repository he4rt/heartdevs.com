<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Enums;

use Filament\Support\Contracts\HasLabel;

enum CredentialsType: string implements HasLabel
{
    case OAuth2 = 'oauth2';
    case ApiKey = 'api_key';
    case Basic = 'basic';

    public function getLabel(): string
    {
        return match ($this) {
            self::OAuth2 => 'OAuth 2.0',
            self::ApiKey => 'API Key',
            self::Basic => 'Basic Auth',
        };
    }
}
