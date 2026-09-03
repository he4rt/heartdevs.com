<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Support\PublicProfileCache;
use Illuminate\Support\Facades\Blade;

function renderLink(?User $user): string
{
    return Blade::render(
        '<x-panel-app::profile-link :user="$user">conteudo</x-panel-app::profile-link>',
        ['user' => $user],
    );
}

it('links to the public profile', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    expect(renderLink($user))
        ->toContain('href="'.route('profile.public', 'danielhe4rt').'"')
        ->toContain('conteudo');
});

it('points the hovercard at the card endpoint', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    expect(str_replace('\\/', '/', renderLink($user)))
        ->toContain(route('profile.card', 'danielhe4rt'));
});

it('shares one card cache across every link on the page', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    expect(renderLink($user))
        ->toContain('window.__profileCards')
        ->toContain('ttl: '.PublicProfileCache::TTL_SECONDS * 1_000);
});

it('does not link a banned author', function (): void {
    $user = User::factory()->create(['username' => 'banido', 'banned_at' => now()]);

    expect(renderLink($user))
        ->not->toContain('<a')
        ->and(renderLink($user))->toContain('conteudo');
});

it('does not link a missing author', function (): void {
    expect(renderLink(user: null))
        ->not->toContain('<a')
        ->and(renderLink(user: null))->toContain('conteudo');
});

it('does not link an author without a username', function (): void {
    expect(renderLink(new User(['name' => 'Sem Username'])))
        ->not->toContain('<a');
});
