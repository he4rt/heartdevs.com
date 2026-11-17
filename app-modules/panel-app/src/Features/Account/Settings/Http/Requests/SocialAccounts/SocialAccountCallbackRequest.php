<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts;

use He4rt\Identity\SocialAccounts\Queries\AvailableSocialProviders;
use He4rt\Identity\SocialAccounts\SocialProvider;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class SocialAccountCallbackRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'provider')] SocialProvider $provider, AvailableSocialProviders $providers): Response
    {
        return $providers->isEnabled($provider) ? Response::allow() : Response::denyAsNotFound();
    }
}
