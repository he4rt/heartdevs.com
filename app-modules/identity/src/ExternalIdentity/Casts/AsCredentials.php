<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Casts;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<ClientAccessManager, ClientAccessManager>
 */
class AsCredentials implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ClientAccessManager
    {
        $payload = json_decode((string) $value, true);

        return ClientAccessManager::makeFromPayload($payload ?? []);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode([
            'client_id' => $value->clientId,
            'client_secret' => $value->clientSecret,
            'access_token' => $value->accessToken,
            'refresh_token' => $value->refreshToken,
            'expires_in' => $value->expiresIn,
            'username' => $value->username,
            'password' => $value->password,
            'api_key' => $value->apiKey,
        ]);
    }
}
