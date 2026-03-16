<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;
use He4rt\Identity\Tenant\Models\Tenant;

class TenantLogoutResponse implements LogoutResponse
{
    public function toResponse($request)
    {

        $tenantSlug = session()->get('tenant');
        $panel = session()->get('panel');
        $tenant = Tenant::query()->where('slug', $tenantSlug)->first();

        Filament::setCurrentPanel($panel);

        return redirect()->to(
            Filament::hasLogin() ? Filament::getLoginUrl(['tenant' => $tenantSlug]) : Filament::getUrl($tenant),
        );
    }
}
