<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Http\Requests\SocialAccounts;

use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteSocialAccountRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'socialAccount')] SocialAccount $account, #[CurrentUser] User $user): Response
    {
        return $account->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }
}
