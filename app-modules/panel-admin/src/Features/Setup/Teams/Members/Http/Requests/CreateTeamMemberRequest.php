<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Members\Http\Requests;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTeamMemberRequest extends FormRequest
{
    private User $member;

    public function authorize(#[RouteParameter(parameter: 'team')] Team $team, #[CurrentUser] User $actor): bool
    {
        return $actor->can('update', $team);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
        ];
    }

    public function member(): User
    {
        return $this->member;
    }

    protected function passedValidation(): void
    {
        $this->member = User::query()->findOrFail($this->safe()->string('user_id')->toString());
    }
}
