<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth;

use App\Data\Support\EnumOptionData;
use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\Auth\CreateNewUser;
use He4rt\Identity\Users\Auth\ResetUserPassword;
use He4rt\Identity\Users\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

/**
 * Fortify owns the auth routes and their logic. This class gives it the panel-app pages and the domain actions.
 */
final class FortifyPages
{
    public static function register(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        app()->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        Fortify::authenticateThrough(self::loginPipeline(...));

        Fortify::loginView(fn (Request $request): Response => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'canUsePasskeys' => resolve(AuthenticationSettings::class)->passkeysEnabled,
            'socialProviders' => collect(resolve(AvailableSocialProviders::class)->enabled())->map(EnumOptionData::fromEnum(...))->values()->all(),
            'status' => self::status($request),
        ]));
        Fortify::registerView(fn (): Response => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));
        Fortify::requestPasswordResetLinkView(fn (Request $request): Response => Inertia::render('auth/forgot-password', [
            'status' => self::status($request),
        ]));
        Fortify::resetPasswordView(fn (Request $request): Response => Inertia::render('auth/reset-password', [
            'email' => $request->string('email')->toString(),
            'token' => (string) $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));
        Fortify::verifyEmailView(fn (Request $request): Response => Inertia::render('auth/verify-email', [
            'status' => self::status($request),
        ]));
        Fortify::confirmPasswordView(fn (Request $request): Response => Inertia::render('auth/confirm-password', self::confirmationMethods($request)));
        Fortify::twoFactorChallengeView(fn (): Response => Inertia::render('auth/two-factor-challenge'));

        self::limitAttempts();
    }

    /**
     * Fortify's default login pipeline, with the two factor challenge only while the settings turn two factor on.
     *
     * @return array<int, class-string>
     */
    private static function loginPipeline(): array
    {
        $challengesTwoFactor = resolve(AuthenticationSettings::class)->twoFactorEnabled;

        return [
            CanonicalizeUsername::class,
            ...($challengesTwoFactor ? [RedirectsIfTwoFactorAuthenticatable::class] : []),
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
            MarkPasswordAsConfirmed::class,
        ];
    }

    /**
     * A user without a password confirms with a connected provider or a passkey.
     *
     * @return array{hasPassword: bool, socialProviders: array<int, EnumOptionData>, canUsePasskeys: bool}
     */
    private static function confirmationMethods(Request $request): array
    {
        $user = $request->user();

        if (!$user instanceof User) {
            return ['hasPassword' => true, 'socialProviders' => [], 'canUsePasskeys' => false];
        }

        $enabled = resolve(AvailableSocialProviders::class)->enabled();

        return [
            'hasPassword' => $user->hasPassword(),
            'socialProviders' => $user->socialAccounts()->get()
                ->map(fn (SocialAccount $account): SocialProvider => $account->provider)
                ->filter(fn (SocialProvider $provider): bool => in_array($provider, $enabled, strict: true))
                ->map(EnumOptionData::fromEnum(...))
                ->values()
                ->all(),
            'canUsePasskeys' => resolve(AuthenticationSettings::class)->passkeysEnabled && $user->passkeys()->exists(),
        ];
    }

    private static function status(Request $request): ?string
    {
        $status = $request->session()->get('status');

        return is_string($status) ? $status : null;
    }

    private static function limitAttempts(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)->by((string) $request->session()->get('login.id')));

        RateLimiter::for('passkeys', fn (Request $request): Limit => Limit::perMinute(10)->by(
            ($request->string('credential.id')->toString() ?: $request->session()->getId()).'|'.$request->ip(),
        ));
    }
}
