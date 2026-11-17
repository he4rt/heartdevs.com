<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

/**
 * The same window as Laravel's password.confirm middleware, for a user who has no password to type.
 */
final readonly class RecentPasswordConfirmation
{
    public function exists(Request $request): bool
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);
        $secondsSince = Date::now()->getTimestamp() - (is_numeric($confirmedAt) ? (int) $confirmedAt : 0);

        return $secondsSince < config()->integer('auth.password_timeout', 10_800);
    }
}
