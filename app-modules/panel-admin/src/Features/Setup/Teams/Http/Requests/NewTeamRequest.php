<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class NewTeamRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('create', Team::class);
    }
}
