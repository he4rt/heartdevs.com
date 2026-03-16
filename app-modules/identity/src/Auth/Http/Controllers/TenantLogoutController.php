<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Controllers;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;
use He4rt\Identity\Auth\Http\Responses\TenantLogoutResponse;

class TenantLogoutController
{
    public function __invoke(string $tenantSlug): LogoutResponse
    {
        $panel = session()->get('panel');
        Filament::auth()->logout();

        session()->invalidate();
        session()->regenerateToken();
        session()->put('tenant', $tenantSlug);
        session()->put('panel', $panel);

        return resolve(TenantLogoutResponse::class);
    }
}
