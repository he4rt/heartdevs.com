<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthResultDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Auth;

final readonly class HandleOAuthCallbackAction
{
    public function __construct(
        private FindOrCreateUserByProvider $findOrCreateUser,
        private AttachProviderToUser $attachProvider,
    ) {}

    public function execute(OAuthStateDTO $state, IdentityProvider $provider, string $code): OAuthResultDTO
    {
        $client = $provider->getClient();

        if (!$client instanceof OAuthClientContract) {
            throw OAuthFlowException::clientNotConfigured($provider);
        }

        $access = $client->auth($code);
        $oauthUser = $client->getAuthenticatedUser($access);

        $tenant = Tenant::query()
            ->where('domain', $state->tenant)
            ->orWhere('slug', $state->tenant)
            ->firstOrFail();

        $user = match ($state->intent) {
            OAuthIntent::Login => $this->findOrCreateUser->execute($oauthUser, $tenant),
            OAuthIntent::Link => $this->resolveAuthenticatedUser(),
        };

        $owner = $state->panel === 'admin' ? $tenant : $user;
        $identity = $this->attachProvider->execute($owner, $tenant, $oauthUser, $access);

        $redirectUrl = $state->returnUrl ?? filament()
            ->getPanel($state->panel)
            ->getUrl($tenant);

        return new OAuthResultDTO(
            user: $user,
            tenant: $tenant,
            identity: $identity,
            intent: $state->intent,
            redirectUrl: $redirectUrl,
        );
    }

    private function resolveAuthenticatedUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw OAuthFlowException::unauthenticatedLinkAttempt();
        }

        return $user;
    }
}
