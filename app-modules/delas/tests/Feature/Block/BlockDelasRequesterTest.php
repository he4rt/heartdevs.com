<?php

declare(strict_types=1);

use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\Block\Actions\UnblockDelasRequester;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->block = resolve(BlockDelasRequester::class);
    $this->unblock = resolve(UnblockDelasRequester::class);
});

// Bloquear

test('bloquear deixa a pessoa bloqueada por quem bloqueou', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();

    $block = $this->block->handle($target, $moderator, 'Pedidos repetidos.');

    expect($block->isActive())->toBeTrue();
    expect($block->blocked_by)->toBe($moderator->getKey());
    expect(resolve(DelasEligibility::class)->for($target)->state)->toBe(DelasEligibilityState::Blocked);
});

test('bloquear rejeita a solicitação pendente e registra as duas ações', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();
    $request = DelasTagRequest::factory()->for($target)->pending()->create();

    $this->block->handle($target, $moderator, 'Pedidos repetidos.');

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Rejected);
    expect(DelasTransition::query()->oldest()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Rejected, DelasAction::Blocked]);
});

test('bloquear exige motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();

    expect(fn () => $this->block->handle($target, $moderator, ' '))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('não bloqueia quem é da equipe nem a si mesma', function (string $state): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = $state === 'self' ? $moderator : User::factory()->{$state}()->create();

    expect(fn () => $this->block->handle($target, $moderator, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_block_team'));
})->with([
    'moderadora' => 'delasModerator',
    'líder' => 'delasLead',
    'super admin' => 'superAdmin',
    'a si mesma' => 'self',
]);

test('não bloqueia quem já está bloqueada', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();
    $this->block->handle($target, $moderator, 'primeiro');

    expect(fn () => $this->block->handle($target, $moderator, 'segundo'))
        ->toThrow(DelasException::class, __('delas::exceptions.already_blocked'));
});

test('membra comum não bloqueia', function (): void {
    $member = User::factory()->create();
    $target = User::factory()->create();

    expect(fn () => $this->block->handle($target, $member, 'motivo'))
        ->toThrow(AuthorizationException::class);
});

// Desbloquear

test('desbloquear exige motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $block = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);

    expect(fn () => $this->unblock->handle($block, $moderator, ''))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('moderadora desbloqueia o próprio bloqueio e o histórico registra', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $block = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);

    $this->unblock->handle($block, $moderator, 'Situação esclarecida.');

    $block->refresh();
    expect($block->isActive())->toBeFalse();
    expect($block->lifted_by)->toBe($moderator->getKey());
    expect($block->lift_reason)->toBe('Situação esclarecida.');
    expect(DelasTransition::query()->sole()->action)->toBe(DelasAction::Unblocked);
});

test('moderadora não desbloqueia o bloqueio de outra', function (): void {
    $block = DelasRequesterBlock::factory()->create();
    $otherModerator = User::factory()->delasModerator()->create();

    expect(fn () => $this->unblock->handle($block, $otherModerator, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_unblock_others'));
});

test('líder desbloqueia qualquer bloqueio', function (): void {
    $block = DelasRequesterBlock::factory()->create();
    $lead = User::factory()->delasLead()->create();

    $this->unblock->handle($block, $lead, 'motivo');

    expect($block->fresh()->isActive())->toBeFalse();
});

test('um bloqueio encerrado não é encerrado de novo', function (): void {
    $lead = User::factory()->delasLead()->create();
    $block = DelasRequesterBlock::factory()->lifted()->create();

    expect(fn () => $this->unblock->handle($block, $lead, 'de novo'))
        ->toThrow(DelasException::class, __('delas::exceptions.block_already_lifted'));
});
