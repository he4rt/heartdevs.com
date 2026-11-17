<?php

declare(strict_types=1);

namespace He4rt\Identity\Mcp\Tools\Teams;

use App\Mcp\DevelopmentTool;
use He4rt\Identity\Mcp\Resources\Teams\TeamResource;
use He4rt\Identity\Mcp\Resources\Users\UserResource;
use He4rt\Identity\Teams\Actions\AddTeamMember;
use He4rt\Identity\Teams\Team;
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

#[Description(value: 'Adds a user to a team and writes the member_added entry to the activity log. Adding a member twice changes nothing.')]
#[IsReadOnly(value: false)]
#[IsDestructive(value: false)]
#[IsIdempotent(value: true)]
#[IsOpenWorld(value: false)]
final class AddTeamMemberTool extends DevelopmentTool
{
    public function handle(Request $request, AddTeamMember $addTeamMember): ResponseFactory
    {
        $request->validate([
            'team_id' => ['required', 'uuid', Rule::exists(Team::class, 'id')],
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
        ]);

        $team = Team::query()->findOrFail($request->string('team_id')->toString());
        $user = User::query()->findOrFail($request->string('user_id')->toString());

        $added = $addTeamMember->handle($team, $user);

        return Response::make([TeamResource::link($team), UserResource::link($user)])->withStructuredContent([
            'added' => $added,
            'members' => $team->members()->count(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    protected function output(JsonSchema $schema): array
    {
        return [
            'added' => $schema->boolean()->description('False when the user was a member already.')->required(),
            'members' => $schema->integer()->required(),
        ];
    }

    /**
     * @return array<string, Type>
     */
    protected function arguments(JsonSchema $schema): array
    {
        return [
            'team_id' => $schema->string()->format('uuid')->required(),
            'user_id' => $schema->string()->format('uuid')->required(),
        ];
    }
}
