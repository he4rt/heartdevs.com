<?php

declare(strict_types=1);

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\BuildPublicProfile;
use He4rt\Profile\DTOs\ProfileBadgeData;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->withoutVite();
});

function characterFor(User $user, int $experience = 1_500): Character
{
    return Character::factory()->for($user)->create(['experience' => $experience]);
}

it('renders the level derived from the experience', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    characterFor($user);

    $this->get('/@danielhe4rt')
        ->assertOk()
        ->assertSee('Comunidade')
        ->assertSee('Nível 6');
});

it('renders each earned badge with its name and description', function (): void {
    $user = User::factory()->create(['username' => 'colecionador']);
    $character = characterFor($user);

    $badge = Badge::factory()->create([
        'name' => 'Fundador',
        'description' => 'Esteve na He4rt desde o primeiro dia.',
    ]);

    $character->badges()->attach($badge, ['claimed_at' => now()]);

    $this->get('/@colecionador')
        ->assertOk()
        ->assertSee('Fundador')
        ->assertSee('Esteve na He4rt desde o primeiro dia.');
});

it('never publishes the badge redeem code', function (): void {
    $user = User::factory()->create(['username' => 'colecionador']);
    $character = characterFor($user);

    $badge = Badge::factory()->create([
        'name' => 'Fundador',
        'redeem_code' => 'HE4RT-SECRET-2026',
    ]);

    $character->badges()->attach($badge, ['claimed_at' => now()]);

    $this->get('/@colecionador')
        ->assertOk()
        ->assertSee('Fundador')
        ->assertDontSee('HE4RT-SECRET-2026');
});

it('keeps the redeem code out of the DTO entirely', function (): void {
    $user = User::factory()->create();
    $character = characterFor($user);

    $character->badges()->attach(Badge::factory()->create(), ['claimed_at' => now()]);

    $badge = resolve(BuildPublicProfile::class)->handle($user)->badges[0];

    expect($badge)->toBeInstanceOf(ProfileBadgeData::class)
        ->and($badge)->not->toHaveProperty('redeem_code')
        ->and($badge)->not->toHaveProperty('redeemCode');
});

it('renders the badge image when the badge has one', function (): void {
    Storage::fake('public');

    $user = User::factory()->create(['username' => 'ilustrado']);
    $character = characterFor($user);

    $badge = Badge::factory()->create(['name' => 'Speaker']);
    $badge->addMediaFromString('fake-png')
        ->usingFileName('speaker.png')
        ->toMediaCollection('badge');

    $character->badges()->attach($badge, ['claimed_at' => now()]);

    $this->get('/@ilustrado')
        ->assertOk()
        ->assertSee('speaker.png');
});

it('renders a badge without an image', function (): void {
    $user = User::factory()->create(['username' => 'sem-imagem']);
    $character = characterFor($user);

    $character->badges()->attach(
        Badge::factory()->create(['name' => 'Beta Tester']),
        ['claimed_at' => now()],
    );

    expect(resolve(BuildPublicProfile::class)->handle($user)->badges[0]->imageUrl)->toBeNull();

    $this->get('/@sem-imagem')
        ->assertOk()
        ->assertSee('Beta Tester');
});

it('hides the whole community section when the member never played', function (): void {
    User::factory()->create(['username' => 'vazio']);

    $this->get('/@vazio')
        ->assertOk()
        ->assertDontSee('Comunidade')
        ->assertDontSee('Nível');
});

it('tells how much XP the next level needs', function (): void {
    $user = User::factory()->create(['username' => 'subindo']);

    characterFor($user, experience: 2_400);

    $this->get('/@subindo')
        ->assertOk()
        ->assertSee('2.400 XP')
        ->assertSee('400')
        ->assertSee('para o próximo nível');
});

it('shows only the total at the level cap, where there is no next level', function (): void {
    $user = User::factory()->create(['username' => 'lendario']);

    characterFor($user, experience: 450_000);

    $this->get('/@lendario')
        ->assertOk()
        ->assertSee('Nível 50')
        ->assertSee('450.000 XP')
        ->assertDontSee('para o próximo nível');
});

it('tells how long the person has been a member', function (): void {
    $user = User::factory()->create([
        'username' => 'veterano',
        'created_at' => now()->subMonthsNoOverflow(16),
    ]);

    characterFor($user);

    $this->get('/@veterano')
        ->assertOk()
        ->assertSee('Membro há 1 ano e 4 meses');
});

it('hides the membership line during the first month', function (): void {
    $user = User::factory()->create([
        'username' => 'recem-chegado',
        'created_at' => now()->subDays(10),
    ]);

    characterFor($user);

    $this->get('/@recem-chegado')
        ->assertOk()
        ->assertDontSee('Membro há');
});
