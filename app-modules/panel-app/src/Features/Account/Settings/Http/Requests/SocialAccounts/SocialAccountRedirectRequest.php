<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts;

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class SocialAccountRedirectRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'provider')] SocialProvider $provider, #[CurrentUser] User $user, AvailableSocialProviders $providers): Response
    {
        if (!$providers->isEnabled($provider)) {
            return Response::denyAsNotFound();
        }

        $hasAccountOfProvider = $user->socialAccounts()->where('provider', $provider)->exists();

        return $hasAccountOfProvider
            ? Response::deny(sprintf('You already connected a %s account.', $provider->getLabel()))
            : Response::allow();
    }
}
