<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests;

use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The slug is fixed once the team exists, so it is not a field here.
 */
final class UpdateTeamRequest extends FormRequest
{
    private User $owner;

    public function authorize(#[RouteParameter(parameter: 'team')] Team $team, #[CurrentUser] User $actor): bool
    {
        return $actor->can('update', $team);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(TeamStatus::class)],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'owner_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
        ];
    }

    public function owner(): User
    {
        return $this->owner;
    }

    public function toDto(): TeamData
    {
        return new TeamData(
            name: $this->safe()->string('name')->trim()->toString(),
            description: $this->safe()->string('description')->trim()->toString(),
            status: $this->safe()->enum('status', TeamStatus::class) ?? TeamStatus::Active,
            contactEmail: $this->safe()->string('contact_email')->trim()->lower()->toString(),
        );
    }

    protected function passedValidation(): void
    {
        $this->owner = User::query()->findOrFail($this->safe()->string('owner_id')->toString());
    }
}
