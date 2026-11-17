<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\Setup\Teams\Http\Requests;

use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTeamRequest extends FormRequest
{
    private User $owner;

    public function authorize(#[CurrentUser] User $actor): bool
    {
        return $actor->can('create', Team::class);
    }

    /**
     * An empty slug lets CreateTeam generate one from the name.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(TeamStatus::class)],
            'contact_email' => ['required', 'string', 'email', 'max:255'],
            'owner_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique(Team::class, 'slug')],
        ];
    }

    public function owner(): User
    {
        return $this->owner;
    }

    public function toDto(): TeamData
    {
        $slug = $this->safe()->string('slug')->trim()->toString();

        return new TeamData(
            name: $this->safe()->string('name')->trim()->toString(),
            description: $this->safe()->string('description')->trim()->toString(),
            status: $this->safe()->enum('status', TeamStatus::class) ?? TeamStatus::Active,
            contactEmail: $this->safe()->string('contact_email')->trim()->lower()->toString(),
            slug: $slug === '' ? null : $slug,
        );
    }

    protected function passedValidation(): void
    {
        $this->owner = User::query()->findOrFail($this->safe()->string('owner_id')->toString());
    }
}
