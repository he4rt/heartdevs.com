<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Auth\Http\Requests;

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class SocialConfirmationRedirectRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'provider')] SocialProvider $provider, #[CurrentUser] User $user, AvailableSocialProviders $providers): Response
    {
        $hasAccountOfProvider = $user->socialAccounts()->where('provider', $provider)->exists();

        return $providers->isEnabled($provider) && $hasAccountOfProvider ? Response::allow() : Response::denyAsNotFound();
    }
}
