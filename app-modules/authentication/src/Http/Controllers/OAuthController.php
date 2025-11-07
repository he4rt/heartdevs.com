<?php

declare(strict_types=1);

namespace He4rt\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use He4rt\Authentication\Actions\AuthenticateAction;
use He4rt\Authentication\Enums\OAuthProviderEnum;
use Illuminate\Http\RedirectResponse;

final class OAuthController extends Controller
{
    public function getRedirect(OAuthProviderEnum $provider): RedirectResponse
    {
        session()->put('tenant', request()->query('tenant'));

        return redirect()->to($provider->getClient()->redirectUrl());
    }

    public function getAuthenticate(OAuthProviderEnum $provider, AuthenticateAction $action): RedirectResponse
    {
        $tenantSlug = session()->get('tenant');
        $action->withOAuth($tenantSlug, $provider, request()->input('code'));

        return redirect()->intended('/admin');
    }
}
