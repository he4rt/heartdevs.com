<?php

declare(strict_types=1);

use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\CorrectDelasReason;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

function rejectionBy(User $moderator, string $reason = 'Perfil incompleo.'): DelasTransition
{
    resolve(RejectDelasRequest::class)->handle(DelasTagRequest::factory()->pending()->create(), $moderator, $reason);

    return DelasTransition::query()->where('action', DelasAction::Rejected)->latest('created_at')->firstOrFail();
}

beforeEach(function (): void {
    $this->correct = resolve(CorrectDelasReason::class);
});

test('a correção é uma linha nova, de quem corrigiu, ligada à original', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $correction = $this->correct->handle($original, $moderator, 'Perfil incompleto.');

    expect($correction->action)->toBe(DelasAction::ReasonCorrected);
    expect($correction->corrects_id)->toBe($original->getKey());
    expect($correction->actor_id)->toBe($moderator->getKey());
});

test('depois da correção, a original continua como foi escrita, mas vale o texto novo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $this->correct->handle($original, $moderator, 'Perfil incompleto.');

    $original->refresh();
    expect($original->reason)->toBe('Perfil incompleo.');
    expect($original->currentReason())->toBe('Perfil incompleto.');
    expect($original->request->fresh()->decision_reason)->toBe('Perfil incompleto.');
});

test('vale sempre a correção mais recente', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $this->correct->handle($original, $moderator, 'Primeira correção.');
    $this->travel(1)->minutes();
    $this->correct->handle($original->fresh(), $moderator, 'Segunda correção.');

    expect($original->fresh()->currentReason())->toBe('Segunda correção.');
    expect(DelasTransition::query()->where('corrects_id', $original->getKey())->count())->toBe(2);
});

test('corrigir o motivo de um bloqueio atualiza o bloqueio', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $block = resolve(BlockDelasRequester::class)->handle(User::factory()->create(), $moderator, 'Pedidos repetidso.');
    $original = DelasTransition::query()->where('action', DelasAction::Blocked)->sole();

    $this->correct->handle($original, $moderator, 'Pedidos repetidos.');

    expect($block->fresh()->reason)->toBe('Pedidos repetidos.');
});

test('quem escreveu não corrige depois do prazo', function (): void {
    config(['delas.reason_correction_hours' => 24]);
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $this->travel(25)->hours();

    expect(fn () => $this->correct->handle($original, $moderator, 'Tarde demais.'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_correct_reason', ['hours' => 24]));
});

test('outra moderadora não corrige o motivo de quem escreveu', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());
    $otherModerator = User::factory()->delasModerator()->create();

    expect(fn () => $this->correct->handle($original, $otherModerator, 'Outro texto.'))
        ->toThrow(DelasException::class);
});

test('a líder corrige qualquer motivo, a qualquer momento', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());
    $lead = User::factory()->delasLead()->create();
    $this->travel(30)->days();

    $correction = $this->correct->handle($original, $lead, 'Motivo revisado pela liderança.');

    expect($original->fresh()->currentReason())->toBe($correction->reason);
});

test('recusa motivo vazio', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator, 'Motivo.');

    expect(fn () => $this->correct->handle($original, $moderator, '  '))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('recusa motivo igual ao atual', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator, 'Motivo.');

    expect(fn () => $this->correct->handle($original, $moderator, 'Motivo.'))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_unchanged'));
});

test('recusa corrigir linhas sem motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $approval = DelasTransition::factory()->create([
        'action' => DelasAction::Approved,
        'actor_id' => $moderator->getKey(),
    ]);

    expect(fn () => $this->correct->handle($approval, $moderator, 'Qualquer coisa.'))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_not_correctable'));
});

test('quem não modera não corrige nada', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());
    $member = User::factory()->create();

    expect(fn () => $this->correct->handle($original, $member, 'Texto.'))
        ->toThrow(AuthorizationException::class);
});
