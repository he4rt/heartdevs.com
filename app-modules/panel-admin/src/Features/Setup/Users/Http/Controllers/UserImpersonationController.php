<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Controllers;

use App\Enums\ToastType;
use App\Http\Controllers\Concerns\FlashesToast;
use He4rt\Identity\Users\Actions\StartImpersonation;
use He4rt\Identity\Users\Exceptions\CannotImpersonate;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Features\Setup\Users\Http\Requests\CreateUserImpersonationRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The target is not a super admin, so it leaves the admin through a full page visit.
 */
final readonly class UserImpersonationController
{
    use FlashesToast;

    public function store(CreateUserImpersonationRequest $request, #[CurrentUser] User $admin, User $user, StartImpersonation $startImpersonation): Response
    {
        try {
            $startImpersonation->handle($admin, $user, returnTo: route('panel-admin::users.index'));
        } catch (CannotImpersonate $cannotImpersonate) {
            return $this->back($cannotImpersonate->getMessage(), ToastType::Error);
        }

        return Inertia::location($request->redirectTo());
    }
}
