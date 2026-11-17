<?php

declare(strict_types=1);

use App\Mcp\DevelopmentServer;
use He4rt\Identity\Mcp\Tools\Teams\AddTeamMemberTool;
use He4rt\Identity\Mcp\Tools\Teams\CreateTeamTool;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Str;

it('names a module tool after its module', function (): void {
    expect(new CreateTeamTool()->name())->toBe('identity-create-team')
        ->and(new AddTeamMemberTool()->name())->toBe('identity-add-team-member');
});

it('creates a team through the action and returns what the action decided', function (): void {
    $owner = User::factory()->create();

    $response = DevelopmentServer::tool(CreateTeamTool::class, ['owner_id' => $owner->id, 'name' => 'Acme'])->assertOk();

    $team = Team::query()->where('name', 'Acme')->sole();
    $result = mcpResult($response);

    expect($team->owner_id)->toBe($owner->id)
        ->and($team->contact_email)->toBe($owner->email)
        ->and($result['structuredContent'])->toMatchArray(['team_id' => $team->id, 'slug' => $team->slug, 'status' => 'active', 'members' => 1])
        ->and(collect($result['structuredContent']['activity'])->pluck('event')->all())->toBe(['created', 'member_added'])
        ->and(collect($result['content'])->where('type', 'resource_link')->pluck('uri')->all())->toBe([
            "development://identity/teams/{$team->id}",
            "development://identity/users/{$owner->id}",
        ]);
});

it('refuses an owner that does not exist', function (): void {
    DevelopmentServer::tool(CreateTeamTool::class, ['owner_id' => Str::uuid()->toString(), 'name' => 'Acme'])
        ->assertHasErrors(['owner id']);

    expect(Team::query()->count())->toBe(0);
});

it('adds a member once and writes the activity log once', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $first = DevelopmentServer::tool(AddTeamMemberTool::class, ['team_id' => $team->id, 'user_id' => $user->id])->assertOk();
    $second = DevelopmentServer::tool(AddTeamMemberTool::class, ['team_id' => $team->id, 'user_id' => $user->id])->assertOk();

    expect(mcpResult($first)['structuredContent'])->toMatchArray(['added' => true, 'members' => 1])
        ->and(mcpResult($first)['structuredContent']['activity'])->toBe([['event' => 'member_added', 'subject_type' => 'teams', 'subject_id' => $team->id]])
        ->and(mcpResult($second)['structuredContent'])->toMatchArray(['added' => false, 'members' => 1, 'activity' => []])
        ->and($team->members()->whereKey($user->id)->exists())->toBeTrue();
});
