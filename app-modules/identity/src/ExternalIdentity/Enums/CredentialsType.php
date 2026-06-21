<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Contracts\HasLabel;

enum CredentialsType: string implements HasLabel
{
    use StringifyEnum;

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
