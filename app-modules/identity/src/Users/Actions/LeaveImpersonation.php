<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Users\Exceptions\CannotImpersonate;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use Illuminate\Auth\AuthManager;

final readonly class LeaveImpersonation
{
    public function __construct(
        private Impersonation $impersonation,
        private AuthManager $auth,
    ) {}

    /**
     * @return string the URL to return to
     *
     * @throws CannotImpersonate
     */
    public function handle(): string
    {
        $impersonator = $this->impersonation->impersonator();
        $target = $this->auth->guard('web')->user();
        throw_unless($impersonator instanceof User && $target instanceof User, CannotImpersonate::because('There is no impersonation to leave.'));

        $returnTo = $this->impersonation->end($impersonator);

        AuditActivity::event($target, 'impersonation_ended', ['impersonator_id' => $impersonator->getKey()], 'Impersonation ended', User::class);

        return $returnTo;
    }
}
