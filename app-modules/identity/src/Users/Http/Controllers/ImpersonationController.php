<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Http\Controllers;

use He4rt\Identity\Users\Actions\LeaveImpersonation;
use He4rt\Identity\Users\Http\Requests\DeleteImpersonationRequest;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A full page visit, because the admin returns to another app than the one the user was in.
 */
final readonly class ImpersonationController
{
    public function destroy(DeleteImpersonationRequest $request, LeaveImpersonation $leaveImpersonation): Response
    {
        return Inertia::location($leaveImpersonation->handle());
    }
}
