<?php

declare(strict_types=1);

use He4rt\Community\Retrospective\Actions\ResolvePeople;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

function linkIdentity(User $user, IdentityProvider $provider, array $metadata): ExternalIdentity
{
    return ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => $provider,
        'metadata' => $metadata,
    ]);
}

it('usa o username da conta GitHub vinculada antes de qualquer outra fonte', function (): void {
    $user = User::factory()->create();
    linkIdentity($user, IdentityProvider::GitHub, ['username' => 'danielhe4rt']);
    linkIdentity($user, IdentityProvider::Discord, ['avatar' => 'https://cdn.discordapp.com/avatars/1/abc.png']);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->avatar)->toBe('https://github.com/danielhe4rt.png');
});

it('cai para o avatar do Discord quando não há conta GitHub', function (): void {
    $user = User::factory()->create();
    linkIdentity($user, IdentityProvider::Discord, ['avatar' => 'https://cdn.discordapp.com/avatars/1/abc.png']);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->avatar)->toBe('https://cdn.discordapp.com/avatars/1/abc.png');
});

it('monta a URL da CDN quando o Discord guardou só o hash do avatar', function (): void {
    $user = User::factory()->create();
    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '286313989237899276',
        'metadata' => ['avatar' => 'abc123'],
    ]);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->avatar)->toBe('https://cdn.discordapp.com/avatars/286313989237899276/abc123.png');
});

it('devolve null para a view mostrar as iniciais quando não há GitHub, Discord nem upload', function (): void {
    $user = User::factory()->create(['username' => 'fulana']);

    $person = resolve(ResolvePeople::class)->execute([$user->id])[$user->id];

    expect($person->avatar)->toBeNull();
});
