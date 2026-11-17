<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;

final class ShowTeamRequest extends FormRequest
{
    public function authorize(#[RouteParameter(parameter: 'team')] Team $team, #[CurrentUser] User $actor): bool
    {
        return $actor->can('view', $team);
    }
}
