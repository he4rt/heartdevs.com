<?php

declare(strict_types=1);

namespace He4rt\Identity\Mcp\Resources\Users;

use He4rt\Identity\Permissions\Role;
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

/**
 * Shows what the admin panel shows a super admin, the email included, so an agent can sign in as the user.
 * Never a secret: no password hash, no two factor secret or recovery codes, no token.
 */
#[Name(value: 'identity-user')]
#[Description(value: 'The current state of a user: email, roles, teams and two factor status. Users from create-records have the password "password".')]
#[MimeType(value: 'application/json')]
final class UserResource extends Resource implements HasUriTemplate
{
    public static function uriFor(string $userId): string
    {
        return "development://identity/users/{$userId}";
    }

    public static function link(User $user): Response
    {
        return Response::resourceLink(
            uri: self::uriFor($user->id),
            name: 'identity-user',
            mimeType: 'application/json',
            title: "User {$user->name}",
            description: 'The current state of the user.',
        );
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate(self::uriFor('{user}'));
    }

    public function handle(Request $request): Response
    {
        $user = User::query()->with(['roles', 'tenants'])->find($request->string('user')->toString());

        if (!$user instanceof User) {
            return Response::error(sprintf('No user has the id [%s].', $request->string('user')));
        }

        return Response::json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'roles' => $user->roles->map(fn (Role $role): string => $role->name)->values()->all(),
            'teams' => $user->tenants->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->values()->all(),
            'created_at' => $user->created_at->toIso8601String(),
        ]);
    }
}
