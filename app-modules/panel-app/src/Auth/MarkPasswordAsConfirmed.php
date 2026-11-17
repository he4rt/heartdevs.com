<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth;

use Closure;
use Illuminate\Http\Request;

/**
 * The user typed the password to log in, so a page behind password.confirm does not ask for it again.
 */
final readonly class MarkPasswordAsConfirmed
{
    /** @param  Closure(Request): mixed  $next */
    public function handle(Request $request, Closure $next): mixed
    {
        $request->session()->passwordConfirmed();

        return $next($request);
    }
}
