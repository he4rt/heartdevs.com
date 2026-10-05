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
use He4rt\Identity\User\Models\User;
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
            return 'https://github.test/oauth?state='.urlencode((string) $state);
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

function githubStateIssuedToSession(OAuthIntent $intent, ?string $returnUrl = null): OAuthStateDTO
{
    session()->put('oauth_state_nonce.github', 'session-nonce');

    return new OAuthStateDTO(
        intent: $intent,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: $returnUrl,
        nonce: 'session-nonce',
    );
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

    $targetUrl = callGithubCallback(githubStateIssuedToSession(OAuthIntent::Login, '/app?source=oauth'));

    expect($targetUrl)
        ->toContain('source=oauth')
        ->toContain('oauth_provider=github')
        ->and(Auth::check())->toBeTrue();
});

test('denied app oauth login does not mark a provider in the redirect URL', function (): void {
    $state = githubStateIssuedToSession(OAuthIntent::Login, '/app/login');

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

test('redirect binds the state nonce to the session and the callback accepts it', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    bindControllerGithubClient();

    $providerUrl = resolve(OAuthController::class)->getRedirect('app', 'github')->getTargetUrl();
    parse_str((string) parse_url($providerUrl, PHP_URL_QUERY), $query);
    $state = OAuthStateDTO::fromEncryptedString($query['state']);

    expect($state->nonce)->toBeString()->toBe(session('oauth_state_nonce.github'));

    callGithubCallback($state);

    expect(Auth::check())->toBeTrue();
});

test('callback with a state issued to another session is rejected', function (): void {
    bindControllerGithubClient();

    $targetUrl = callGithubCallback(new OAuthStateDTO(
        intent: OAuthIntent::Login,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        returnUrl: 'https://attacker.test',
        nonce: 'attacker-nonce',
    ));

    expect($targetUrl)->toBe(url('/'))
        ->and(Auth::check())->toBeFalse();
});

test('forged link callback does not attach the attacker account to the logged-in user', function (): void {
    bindControllerGithubClient();
    $victim = User::factory()->create();
    $this->actingAs($victim);
    session()->put('oauth_state_nonce.github', 'victim-nonce');

    callGithubCallback(new OAuthStateDTO(
        intent: OAuthIntent::Link,
        provider: IdentityProvider::GitHub,
        panel: 'app',
        nonce: 'attacker-nonce',
    ));

    expect($victim->providers()->count())->toBe(0);
});

test('state nonce is single use', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    bindControllerGithubClient();
    $state = githubStateIssuedToSession(OAuthIntent::Login);

    callGithubCallback($state);
    Auth::logout();

    $replayTargetUrl = callGithubCallback($state);

    expect($replayTargetUrl)->toBe(url('/'))
        ->and(Auth::check())->toBeFalse();
});
