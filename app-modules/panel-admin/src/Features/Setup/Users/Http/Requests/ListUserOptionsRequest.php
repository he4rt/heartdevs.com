<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Users\Http\Requests;

use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class ListUserOptionsRequest extends FormRequest
{
    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('viewAny', User::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'team' => ['nullable', 'uuid'],
        ];
    }

    public function search(): string
    {
        return $this->safe()->string('search')->trim()->toString();
    }

    /**
     * The members of this team are left out of the options.
     */
    public function excludedTeamId(): ?string
    {
        $teamId = $this->safe()->string('team')->toString();

        return $teamId === '' ? null : $teamId;
    }
}
