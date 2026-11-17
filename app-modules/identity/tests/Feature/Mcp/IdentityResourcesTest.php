<?php

declare(strict_types=1);

use App\Mcp\DevelopmentServer;
use He4rt\Identity\Mcp\Resources\Teams\TeamResource;
use He4rt\Identity\Mcp\Resources\Users\UserResource;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

it('shows the current state of a team, with its members and its last activity', function (): void {
    $team = Team::factory()->create(['name' => 'Acme']);
    $member = User::factory()->create(['name' => 'Ana']);
    $team->members()->attach($member);
    $team->delete();

    DevelopmentServer::resource(TeamResource::class, ['team' => $team->id])
        ->assertOk()
        ->assertSee(['"name":"Acme"', '"name":"Ana"', '"event":"deleted"', '"deleted_at":"']);
});

it('shows a user with the email and without any secret', function (): void {
    $user = User::factory()->withTwoFactor()->create(['email' => 'ana@example.com']);

    DevelopmentServer::resource(UserResource::class, ['user' => $user->id])
        ->assertOk()
        ->assertSee(['"email":"ana@example.com"', '"two_factor_enabled":true'])
        ->assertDontSee([(string) $user->password, 'two_factor_secret', 'recovery']);
});

it('says when the record does not exist', function (string $resource, string $variable, string $message): void {
    DevelopmentServer::resource($resource, [$variable => '01a1d6c0-0000-7000-8000-000000000000'])->assertHasErrors([$message]);
})->with([
    'team' => [TeamResource::class, 'team', 'No team has the id'],
    'user' => [UserResource::class, 'user', 'No user has the id'],
]);
