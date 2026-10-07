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

test('a correção é uma linha nova e a original continua como foi escrita', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $correction = resolve(CorrectDelasReason::class)->handle($original, $moderator, 'Perfil incompleto.');

    expect($correction->action)->toBe(DelasAction::ReasonCorrected)
        ->and($correction->corrects_id)->toBe($original->getKey())
        ->and($correction->actor_id)->toBe($moderator->getKey())
        ->and($original->fresh()->reason)->toBe('Perfil incompleo.')
        ->and($original->fresh()->currentReason())->toBe('Perfil incompleto.')
        ->and($original->request->fresh()->decision_reason)->toBe('Perfil incompleto.');
});

test('vale sempre a correção mais recente', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    resolve(CorrectDelasReason::class)->handle($original, $moderator, 'Primeira correção.');
    $this->travel(1)->minutes();
    resolve(CorrectDelasReason::class)->handle($original->fresh(), $moderator, 'Segunda correção.');

    expect($original->fresh()->currentReason())->toBe('Segunda correção.')
        ->and(DelasTransition::query()->where('corrects_id', $original->getKey())->count())->toBe(2);
});

test('corrigir o motivo de um bloqueio atualiza o bloqueio', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $block = resolve(BlockDelasRequester::class)->handle(User::factory()->create(), $moderator, 'Pedidos repetidso.');
    $original = DelasTransition::query()->where('action', DelasAction::Blocked)->sole();

    resolve(CorrectDelasReason::class)->handle($original, $moderator, 'Pedidos repetidos.');

    expect($block->fresh()->reason)->toBe('Pedidos repetidos.');
});

test('quem escreveu corrige dentro do prazo, mas não depois', function (): void {
    config(['delas.reason_correction_hours' => 24]);
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator);

    $this->travel(25)->hours();

    expect(fn () => resolve(CorrectDelasReason::class)->handle($original, $moderator, 'Tarde demais.'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_correct_reason', ['hours' => 24]));
});

test('outra moderadora não corrige o motivo de quem escreveu', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());

    expect(fn () => resolve(CorrectDelasReason::class)->handle($original, User::factory()->delasModerator()->create(), 'Outro texto.'))
        ->toThrow(DelasException::class);
});

test('a líder corrige qualquer motivo, a qualquer momento', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());
    $this->travel(30)->days();

    $correction = resolve(CorrectDelasReason::class)->handle($original, User::factory()->delasLead()->create(), 'Motivo revisado pela liderança.');

    expect($original->fresh()->currentReason())->toBe($correction->reason);
});

test('recusa motivo vazio, motivo igual e linhas sem motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $original = rejectionBy($moderator, 'Motivo.');
    $approval = DelasTransition::factory()->create(['action' => DelasAction::Approved, 'actor_id' => $moderator->getKey()]);

    expect(fn () => resolve(CorrectDelasReason::class)->handle($original, $moderator, '  '))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'))
        ->and(fn () => resolve(CorrectDelasReason::class)->handle($original, $moderator, 'Motivo.'))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_unchanged'))
        ->and(fn () => resolve(CorrectDelasReason::class)->handle($approval, $moderator, 'Qualquer coisa.'))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_not_correctable'));
});

test('quem não modera não corrige nada', function (): void {
    $original = rejectionBy(User::factory()->delasModerator()->create());

    expect(fn () => resolve(CorrectDelasReason::class)->handle($original, User::factory()->create(), 'Texto.'))
        ->toThrow(AuthorizationException::class);
});
