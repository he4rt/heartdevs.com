<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Delas\DelasProfileSection;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->user = User::factory()->withDiscord()->create();
    $this->actingAs($this->user);
});

test('a opção de solicitar aparece para quem pode pedir', function (): void {
    livewire(DelasProfileSection::class)
        ->assertOk()
        ->assertSee(__('panel-app::delas.profile.toggle'))
        ->assertActionVisible('request');
});

test('abrir o pop-up de solicitar ainda não registra nada', function (): void {
    livewire(DelasProfileSection::class)
        ->mountAction('request')
        ->assertActionMounted('request')
        ->assertMountedActionModalSee(__('panel-app::delas.profile.modal.tip'));

    expect(DelasTagRequest::query()->exists())->toBeFalse();
});

test('confirmar no pop-up registra a solicitação como pendente', function (): void {
    livewire(DelasProfileSection::class)
        ->callAction('request')
        ->assertNotified(__('panel-app::delas.profile.sent'))
        ->assertSee(__('panel-app::delas.profile.pending_title'))
        ->assertActionHidden('request');

    expect(DelasTagRequest::query()->sole()->status)->toBe(DelasRequestStatus::Pending);
});

test('com a tag aprovada, o perfil mostra que a pessoa faz parte', function (): void {
    DelasTagRequest::factory()->for($this->user)->approved()->create();

    livewire(DelasProfileSection::class)
        ->assertSee(__('panel-app::delas.profile.member_title'))
        ->assertActionHidden('request');
});

test('em espera, mostra a data da próxima tentativa', function (): void {
    config(['app.display_timezone' => 'America/Sao_Paulo']);
    $this->travelTo('2026-10-06 15:00:00');
    DelasTagRequest::factory()->for($this->user)->rejected()->create(['decided_at' => now()]);

    livewire(DelasProfileSection::class)
        ->assertSee(__('panel-app::delas.profile.cooldown_body', ['date' => '21/10']))
        ->assertActionHidden('request');
});

test('bloqueada, mostra só que não é possível solicitar, sem o motivo', function (): void {
    DelasRequesterBlock::factory()->for($this->user)->create(['reason' => 'motivo interno sigiloso']);

    livewire(DelasProfileSection::class)
        ->assertSee(__('panel-app::delas.profile.blocked_title'))
        ->assertDontSee('motivo interno sigiloso')
        ->assertActionHidden('request');
});

test('sem o Discord, pede para conectar e não deixa solicitar', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(DelasProfileSection::class)
        ->assertSee(__('panel-app::delas.profile.discord_title'))
        ->assertSee(route('oauth.redirect', ['panel' => 'app', 'provider' => 'discord']), escape: false)
        ->assertActionHidden('request');
});
