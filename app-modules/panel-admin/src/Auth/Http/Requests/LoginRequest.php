<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Auth\Http\Requests;

use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Auth\LoginOutcome;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    private const int MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Only super admins pass. Other users get the same message as a wrong password, so the form does not reveal who has an account.
     *
     * A user with two factor on is not signed in yet: the session keeps them under the keys Fortify's challenge reads,
     * and the challenge sends them back to the admin.
     *
     * @throws ValidationException
     */
    public function authenticate(): LoginOutcome
    {
        $this->ensureIsNotRateLimited();

        $user = $this->superAdminWithValidCredentials();
        $remember = $this->safe()->boolean('remember');

        if (!$user instanceof User) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());

        if (resolve(AuthenticationSettings::class)->challengesTwoFactor($user)) {
            $this->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $remember,
                'url.intended' => route('panel-admin::home'),
            ]);

            return LoginOutcome::AwaitingSecondFactor;
        }

        Auth::login($user, $remember);

        return LoginOutcome::SignedIn;
    }

    private function superAdminWithValidCredentials(): ?User
    {
        $provider = Auth::getProvider();
        $user = $provider->retrieveByCredentials(['email' => $this->safe()->string('email')->toString()]);
        $isValidSuperAdmin = $user instanceof User
            && $user->hasRole(Roles::SuperAdmin)
            && $provider->validateCredentials($user, ['password' => $this->safe()->string('password')->toString()]);

        return $isValidSuperAdmin ? $user : null;
    }

    /** @throws ValidationException */
    private function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->safe()->string('email')->toString()).'|'.$this->ip());
    }
}
