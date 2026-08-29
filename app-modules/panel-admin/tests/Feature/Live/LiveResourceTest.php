<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\User\Models\User;
use He4rt\Live\Chat\Actions\SendChatMessage;
use He4rt\Live\Enums\LiveStatus;
use He4rt\Live\Models\Live;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ListLives;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Pages\ViewLive;
use He4rt\PanelAdmin\Live\Resources\LiveResource\Widgets\LiveChatMessages;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config([
        'he4rt.admins' => 'danielhe4rt',
        'app.display_timezone' => 'America/Sao_Paulo',
    ]);

    $this->actingAs(User::factory()->create(['username' => 'danielhe4rt']));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lista lives', function (): void {
    $lives = Live::factory()->count(2)->create(['status' => LiveStatus::Ended]);

    livewire(ListLives::class)
        ->loadTable()
        ->assertCanSeeTableRecords($lives);
});

it('cria live pela action da listagem', function (): void {
    livewire(ListLives::class)
        ->callAction('createLive', ['title' => 'Retrô de agosto', 'description' => 'Balanço'])
        ->assertNotified();

    expect(Live::query()->sole()->title)->toBe('Retrô de agosto');
});

it('bloqueia criação com live corrente aberta', function (): void {
    Live::factory()->create();

    livewire(ListLives::class)
        ->callAction('createLive', ['title' => 'Outra', 'description' => null])
        ->assertNotified();

    expect(Live::query()->count())->toBe(1);
});

it('encerra a live', function (): void {
    $live = Live::factory()->onAir()->create();

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('endLive')
        ->assertNotified();

    expect($live->refresh()->status)->toBe(LiveStatus::Ended);
});

it('rotaciona a stream key', function (): void {
    $live = Live::factory()->create();
    $original = $live->stream_key;

    livewire(ViewLive::class, ['record' => $live->id])
        ->callAction('rotateStreamKey')
        ->assertNotified();

    expect($live->refresh()->stream_key)->not->toBe($original);
});

it('modera mensagem do chat a partir da view da live', function (): void {
    $live = Live::factory()->onAir()->create();
    $author = User::factory()->create();
    resolve(SendChatMessage::class)->execute($author, $live, 'mensagem imprópria');
    $message = Message::query()->sole();

    livewire(LiveChatMessages::class, ['record' => $live])
        ->callAction(TestAction::make('deleteChatMessage')->table($message))
        ->assertNotified();

    expect(Message::query()->count())->toBe(0);
});
