<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Impersonation;

use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Session\Session;

/**
 * The impersonation state of the session. The session keeps the admin id and the URL to return to,
 * so a leave works from any app the user lands in.
 */
final readonly class Impersonation
{
    private const string IMPERSONATOR_KEY = 'impersonation.impersonator_id';

    private const string RETURN_TO_KEY = 'impersonation.return_to';

    public function __construct(
        private AuthManager $auth,
        private Session $session,
    ) {}

    public function reasonToRefuse(User $impersonator, User $target): ?string
    {
        return match (true) {
            $this->isActive() => 'You are already impersonating a user.',
            !$impersonator->hasRole(Roles::SuperAdmin) => 'Only a super admin can impersonate.',
            $impersonator->is($target) => 'You cannot impersonate yourself.',
            $target->hasRole(Roles::SuperAdmin) => 'You cannot impersonate another super admin.',
            default => null,
        };
    }

    public function isActive(): bool
    {
        return $this->session->has(self::IMPERSONATOR_KEY);
    }

    public function impersonator(): ?User
    {
        $id = $this->impersonatorId();

        return $id === null ? null : User::query()->find($id);
    }

    public function impersonatorId(): ?string
    {
        $id = $this->session->get(self::IMPERSONATOR_KEY);

        return is_string($id) ? $id : null;
    }

    public function begin(User $impersonator, User $target, string $returnTo): void
    {
        $this->signInAs($target);
        $this->session->put(self::IMPERSONATOR_KEY, $impersonator->getKey());
        $this->session->put(self::RETURN_TO_KEY, $returnTo);
    }

    /**
     * @return string the URL to return to
     */
    public function end(User $impersonator): string
    {
        $returnTo = $this->session->get(self::RETURN_TO_KEY);

        $this->signInAs($impersonator);
        $this->session->forget([self::IMPERSONATOR_KEY, self::RETURN_TO_KEY]);

        return is_string($returnTo) ? $returnTo : '/';
    }

    /**
     * AuthenticateSession signs out a session whose stored password hash belongs to another user.
     */
    private function signInAs(User $user): void
    {
        $guard = $this->auth->guard('web');
        assert($guard instanceof SessionGuard);

        $guard->login($user);
        $this->session->put('password_hash_'.$this->auth->getDefaultDriver(), $guard->hashPasswordForCookie($user->getAuthPassword()));
    }
}
