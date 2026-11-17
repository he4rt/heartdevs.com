<?php

declare(strict_types=1);

namespace He4rt\Identity\Mcp\Tools\Teams;

use App\Mcp\DevelopmentTool;
use He4rt\Identity\Mcp\Resources\Teams\TeamResource;
use He4rt\Identity\Mcp\Resources\Users\UserResource;
use He4rt\Identity\Teams\Actions\CreateTeam;
use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description(value: 'Creates a team owned by a user. The owner becomes the first member of the team.')]
#[IsReadOnly(value: false)]
#[IsDestructive(value: false)]
#[IsIdempotent(value: false)]
#[IsOpenWorld(value: false)]
final class CreateTeamTool extends DevelopmentTool
{
    public function handle(Request $request, CreateTeam $createTeam): ResponseFactory
    {
        $request->validate([
            'owner_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TeamStatus::class)],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $owner = User::query()->findOrFail($request->string('owner_id')->toString());

        $team = $createTeam->handle($owner, new TeamData(
            name: $request->string('name')->trim()->toString(),
            description: $request->string('description')->trim()->toString(),
            status: $request->enum('status', TeamStatus::class) ?? TeamStatus::Active,
            contactEmail: $request->string('contact_email', $owner->email)->trim()->lower()->toString(),
        ));

        return Response::make([TeamResource::link($team), UserResource::link($owner)])->withStructuredContent([
            'team_id' => $team->id,
            'slug' => $team->slug,
            'status' => $team->status->value,
            'members' => $team->members()->count(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    protected function output(JsonSchema $schema): array
    {
        return [
            'team_id' => $schema->string()->required(),
            'slug' => $schema->string()->description('Generated from the name.')->required(),
            'status' => $schema->string()->required(),
            'members' => $schema->integer()->description('The owner is the first member.')->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    protected function arguments(JsonSchema $schema): array
    {
        return [
            'owner_id' => $schema->string()->format('uuid')->description('The id of the user that owns the team.')->required(),
            'name' => $schema->string()->max(255)->required(),
            'description' => $schema->string()->max(255)->description('Empty when left out.'),
            'status' => $schema->string()->enum(TeamStatus::class)->default(TeamStatus::Active->value),
            'contact_email' => $schema->string()->format('email')->description('The email of the owner when left out.'),
        ];
    }
}
