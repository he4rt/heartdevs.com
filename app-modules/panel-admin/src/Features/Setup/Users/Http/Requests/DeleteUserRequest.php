<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Requests;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteUserRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'user')] User $target, #[CurrentUser] User $actor): Response
    {
        if ($target->is($actor)) {
            return Response::deny('You cannot delete your own account.');
        }

        return $actor->can('delete', $target) ? Response::allow() : Response::deny();
    }
}
