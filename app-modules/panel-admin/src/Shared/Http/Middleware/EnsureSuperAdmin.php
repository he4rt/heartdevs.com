<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Shared\Http\Middleware;

use Closure;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSuperAdmin
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof User) {
            $login = route('panel-admin::login');

            return $request->inertia() ? Inertia::location($login) : redirect()->guest($login);
        }

        abort_unless($user->hasRole(Roles::SuperAdmin), 404);

        return $next($request);
    }
}
