<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\ApiKey;

use App\Contracts\ApiKeyClientContract;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Http;

final class DevToApiKeyClient implements ApiKeyClientContract
{
    public function getAuthenticatedUser(string $apiKey): DevToApiKeyUser
    {
        $response = Http::withHeaders(['api-key' => $apiKey])
            ->timeout(10)
            ->acceptJson()
            ->get(config('integration-devto.api_base_url').'/users/me');

        if ($response->failed()) {
            throw new InvalidApiKeyException(IdentityProvider::DevTo, $response->status());
        }

        return DevToApiKeyUser::make($response->json() ?? []);
    }
}
