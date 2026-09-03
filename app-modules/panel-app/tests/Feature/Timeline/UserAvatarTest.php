<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildProfileCard;
use Illuminate\Support\Facades\Blade;

function renderAvatar(?User $user): string
{
    return Blade::render('<x-panel-app::user-avatar :user="$user" />', ['user' => $user]);
}

it('shows the same initials the profile card shows', function (): void {
    $user = User::factory()->create(['name' => 'Daniel Reis', 'username' => 'danielhe4rt']);

    $card = resolve(BuildProfileCard::class)->handle($user);

    expect($card->initials)->toBe('DR')
        ->and(renderAvatar($user))->toContain('DR');
});

it('falls back to the username when the name has no letters', function (): void {
    $user = User::factory()->create(['name' => '42', 'username' => 'zeta']);

    expect(renderAvatar($user))->toContain('Z')
        ->and(resolve(BuildProfileCard::class)->handle($user)->initials)->toBe('Z');
});

it('renders a placeholder when the author is gone', function (): void {
    expect(renderAvatar(user: null))->toContain('UR');
});
