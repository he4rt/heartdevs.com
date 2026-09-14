<?php

declare(strict_types=1);

use App\Contracts\OAuthClientContract;
use Filament\Facades\Filament;
use He4rt\Identity\Auth\Actions\HandleOAuthCallbackAction;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\Auth\Http\Controllers\OAuthController;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationGithub\OAuth\GitHubOAuthClient;
use Illuminate\Support\Facades\Auth;

function bindControllerGithubClient(): void
{
    $access = new class('access-token', 'refresh-token', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('access-token', 'refresh-token', 3_600);
        }
    };

    $user = new class($access) extends OAuthUserDTO
    {
        public function __construct(OAuthAccessDTO $credentials)
        {
            parent::__construct(
                credentials: $credentials,
                providerId: 'controller-github-id',
                provider: IdentityProvider::GitHub,
                username: 'controller-user',
                name: 'Controller User',
                email: 'controller@example.com',
                avatarUrl: null,
            );
        }

        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials);
        }
    };

    app()->instance(GitHubOAuthClient::class, new readonly class($access, $user) implements OAuthClientContract
    {
        public function __construct(
            private OAuthAccessDTO $access,
            private OAuthUserDTO $user,
        ) {}

        public function redirectUrl(?OAuthStateDTO $state = null): string
        {
            return 'https://github.test/oauth';
        }

        public function auth(string $code): OAuthAccessDTO
        {
            return $this->access;
        }

        public function getAuthenticatedUser(OAuthAccessDTO $credentials): OAuthUserDTO
        {
            return $this->user;
        }
    });
}

function callGithubCallback(OAuthStateDTO $state): string
{
    request()->merge([
        'state' => (string) $state,
        'code' => 'auth-code',
    ]);

    return resolve(OAuthController::class)
        ->getAuthenticate('github', resolve(HandleOAuthCallbackAction::class))
        ->getTargetUrl();
}

test('successful app oauth login marks the provider in the redirect URL', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    bindControllerGithubClient();

    $targetUrl = callGithubCallback(new OAuthStateDTO(
        intent: OAuthIntent::Login,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: '/app?source=oauth',
    ));

    expect($targetUrl)
        ->toContain('source=oauth')
        ->toContain('oauth_provider=github')
        ->and(Auth::check())->toBeTrue();
});

test('denied app oauth login does not mark a provider in the redirect URL', function (): void {
    $state = new OAuthStateDTO(
        intent: OAuthIntent::Login,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: '/app/login',
    );

    request()->merge([
        'state' => (string) $state,
        'error' => 'access_denied',
    ]);

    $targetUrl = resolve(OAuthController::class)
        ->getAuthenticate('github', resolve(HandleOAuthCallbackAction::class))
        ->getTargetUrl();

    expect($targetUrl)
        ->toContain('/app/login')
        ->not->toContain('oauth_provider=');
});
