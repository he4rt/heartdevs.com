<?php

declare(strict_types=1);

namespace He4rt\Identity\Mcp\Resources\Teams;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;
use Spatie\Activitylog\Models\Activity;

#[Name(value: 'identity-team')]
#[Description(value: 'The current state of a team: its owner, its members and its last activity log entries. A trashed team shows its deleted_at.')]
#[MimeType(value: 'application/json')]
final class TeamResource extends Resource implements HasUriTemplate
{
    private const int LAST_ACTIVITIES = 10;

    public static function uriFor(string $teamId): string
    {
        return "development://identity/teams/{$teamId}";
    }

    public static function link(Team $team): Response
    {
        return Response::resourceLink(
            uri: self::uriFor($team->id),
            name: 'identity-team',
            mimeType: 'application/json',
            title: "Team {$team->name}",
            description: 'The current state of the team.',
        );
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate(self::uriFor('{team}'));
    }

    public function handle(Request $request): Response
    {
        $team = Team::withTrashed()->with(['owner', 'members'])->find($request->string('team')->toString());

        if (!$team instanceof Team) {
            return Response::error(sprintf('No team has the id [%s].', $request->string('team')));
        }

        return Response::json([
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'description' => $team->description,
            'status' => $team->status->value,
            'owner' => ['id' => $team->owner->id, 'name' => $team->owner->name],
            'members' => $team->members->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name])->values()->all(),
            'created_at' => $team->created_at->toIso8601String(),
            'deleted_at' => $team->deleted_at?->toIso8601String(),
            'last_activity' => Activity::query()
                ->whereMorphedTo('subject', $team)
                ->latest('id')
                ->limit(self::LAST_ACTIVITIES)
                ->get()
                ->map(fn (Activity $activity): array => [
                    'event' => $activity->event,
                    'causer_id' => $activity->causer_id,
                    'request_id' => $activity->getAttribute('request_id'),
                    'created_at' => $activity->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ]);
    }
}
