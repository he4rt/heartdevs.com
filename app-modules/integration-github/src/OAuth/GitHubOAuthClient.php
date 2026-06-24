<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\OAuth;

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationGithub\OAuth\DTO\GitHubOAuthAccessDTO;
use He4rt\IntegrationGithub\OAuth\DTO\GitHubOAuthUserDTO;
use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\GitHubOAuthConnector;
use He4rt\IntegrationGithub\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationGithub\Transport\Requests\Users\GetCurrentUser;
use He4rt\IntegrationGithub\Transport\Requests\Users\GetUserEmails;

final readonly class GitHubOAuthClient implements OAuthClientContract
{
    public function __construct(
        private GitHubOAuthConnector $oauthConnector,
        private GitHubApiConnector $apiConnector,
    ) {}

    public function redirectUrl(?OAuthStateDTO $state = null): string
    {
        return 'https://github.com/login/oauth/authorize?'.http_build_query([
            'client_id' => $this->oauthConnector->clientId,
            'redirect_uri' => $this->callbackUrl(),
            'scope' => config('services.github.scopes'),
            'state' => (string) $state,
        ]);
    }

    public function auth(string $code): GitHubOAuthAccessDTO
    {
        $response = $this->oauthConnector->send(new ExchangeCodeForToken(
            code: $code,
            clientId: $this->oauthConnector->clientId,
            clientSecret: $this->oauthConnector->getClientSecret(),
            redirectUri: $this->callbackUrl(),
        ));

        /** @var array<string, mixed> $payload */
        $payload = $response->json();
        $tokenExchangeFailed = !isset($payload['access_token']);

        if ($tokenExchangeFailed) {
            throw OAuthFlowException::tokenExchangeFailed('github', $payload['error_description'] ?? $payload['error'] ?? 'unknown');
        }

        return GitHubOAuthAccessDTO::make($payload);
    }

    public function getAuthenticatedUser(OAuthAccessDTO $credentials): GitHubOAuthUserDTO
    {
        $response = $this->apiConnector->send(new GetCurrentUser(
            accessToken: $credentials->accessToken,
        ));

        /** @var array<string, mixed> $userPayload */
        $userPayload = $response->json();

        // GitHub's /user only exposes a *public* email, so it is null when the
        // user keeps their address private. Recover the primary verified email
        // from /user/emails (granted by the user:email scope); otherwise login
        // can't match an existing account by email and forks a duplicate user.
        // When even the fallback can't yield a primary+verified address there is
        // nothing trustworthy to correlate on, so hard-fail instead of forking.
        if (($userPayload['email'] ?? null) === null) {
            $userPayload['email'] = $this->resolvePrimaryEmail($credentials->accessToken)
                ?? throw OAuthFlowException::emailUnavailable('github');
        }

        return GitHubOAuthUserDTO::make($credentials, $userPayload);
    }

    private function resolvePrimaryEmail(string $accessToken): ?string
    {
        $response = $this->apiConnector->send(new GetUserEmails(
            accessToken: $accessToken,
        ));

        if (!$response->ok()) {
            return null;
        }

        // Only the single primary + verified address identifies the account. A
        // GitHub user often has several verified emails (work, school, noreply),
        // so "any verified" could match the wrong one — require both flags.
        return $response->collect()
            ->where('primary', operator: true)
            ->where('verified', operator: true)
            ->value('email');
    }

    private function callbackUrl(): string
    {
        return mb_rtrim(config('app.url'), '/').'/auth/oauth/github';
    }
}
