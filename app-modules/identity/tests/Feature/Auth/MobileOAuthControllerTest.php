<?php

declare(strict_types=1);

use App\Contracts\OAuthClientContract;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationGithub\OAuth\GitHubOAuthClient;
use Illuminate\Support\Facades\Auth;

function bindMobileGithubClient(): void
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
                providerId: 'mobile-github-id',
                provider: IdentityProvider::GitHub,
                username: 'mobile-user',
                name: 'Mobile User',
                email: 'mobile@example.com',
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

test('redirect sends an unsupported provider to a 404', function (): void {
    $this->get('/api/mobile/auth/devto/redirect')->assertNotFound();
});

test('redirect forwards to the provider authorize URL', function (): void {
    bindMobileGithubClient();

    $this->get('/api/mobile/auth/github/redirect')
        ->assertRedirect('https://github.test/oauth');
});

test('a denied mobile authorization on the shared web callback redirects to the app deep link with an error', function (): void {
    // Discord/GitHub/Twitch só conhecem UMA redirect_uri por app: a rota web
    // /auth/oauth/{provider} (OAuthController::getAuthenticate). O login mobile
    // é distinguido pelo intent codificado no state, não por uma rota própria.
    $state = new OAuthStateDTO(
        intent: OAuthIntent::MobileLogin,
        provider: IdentityProvider::GitHub,
        panel: 'mobile',
        returnUrl: 'mobile',
    );

    $response = $this->get('/auth/oauth/github?'.http_build_query([
        'state' => (string) $state,
        'error' => 'access_denied',
    ]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith('he4rtapp://oauth/error')
        ->toContain('error=access_denied');
});

test('a successful mobile login on the shared web callback redirects to the app deep link with an exchange code, without starting a web session', function (): void {
    bindMobileGithubClient();

    $state = new OAuthStateDTO(
        intent: OAuthIntent::MobileLogin,
        provider: IdentityProvider::GitHub,
        panel: 'mobile',
        returnUrl: 'mobile',
    );

    $response = $this->get('/auth/oauth/github?'.http_build_query([
        'state' => (string) $state,
        'code' => 'auth-code',
    ]));

    $response->assertRedirect();

    $location = (string) $response->headers->get('Location');

    expect($location)->toStartWith('he4rtapp://oauth/callback?code=')
        ->and(User::query()->where('username', 'mobile-user')->exists())->toBeTrue()
        ->and(Auth::check())->toBeFalse();
});
