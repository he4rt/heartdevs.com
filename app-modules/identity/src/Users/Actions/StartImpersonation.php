<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Actions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Users\Exceptions\CannotImpersonate;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;

final readonly class StartImpersonation
{
    public function __construct(
        private Impersonation $impersonation,
    ) {}

    /** @throws CannotImpersonate */
    public function handle(User $impersonator, User $target, string $returnTo): void
    {
        $reason = $this->impersonation->reasonToRefuse($impersonator, $target);
        throw_if($reason !== null, CannotImpersonate::because((string) $reason));

        AuditActivity::event($target, 'impersonation_started', ['impersonator_id' => $impersonator->getKey()], 'Impersonation started', User::class);

        $this->impersonation->begin($impersonator, $target, $returnTo);
    }
}
