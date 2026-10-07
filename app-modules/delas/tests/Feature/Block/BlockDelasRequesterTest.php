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

test('bloquear rejeita a solicitação pendente e registra as duas ações', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();
    $request = DelasTagRequest::factory()->for($target)->pending()->create();

    $block = resolve(BlockDelasRequester::class)->handle($target, $moderator, 'Pedidos repetidos.');

    expect($block->isActive())->toBeTrue()
        ->and($block->blocked_by)->toBe($moderator->getKey())
        ->and($request->fresh()->status)->toBe(DelasRequestStatus::Rejected)
        ->and(resolve(DelasEligibility::class)->for($target)->state)->toBe(DelasEligibilityState::Blocked)
        ->and(DelasTransition::query()->oldest()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Rejected, DelasAction::Blocked]);
});

test('bloquear exige motivo', function (): void {
    $moderator = User::factory()->delasModerator()->create();

    expect(fn () => resolve(BlockDelasRequester::class)->handle(User::factory()->create(), $moderator, ' '))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('não bloqueia moderadora, líder, super admin nem a si mesma', function (string $state): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = $state === 'self' ? $moderator : User::factory()->{$state}()->create();

    expect(fn () => resolve(BlockDelasRequester::class)->handle($target, $moderator, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_block_team'));
})->with(['delasModerator', 'delasLead', 'superAdmin', 'self']);

test('não bloqueia quem já está bloqueada', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();
    resolve(BlockDelasRequester::class)->handle($target, $moderator, 'primeiro');

    expect(fn () => resolve(BlockDelasRequester::class)->handle($target, $moderator, 'segundo'))
        ->toThrow(DelasException::class, __('delas::exceptions.already_blocked'));
});

test('membra comum não bloqueia', function (): void {
    expect(fn () => resolve(BlockDelasRequester::class)->handle(User::factory()->create(), User::factory()->create(), 'motivo'))
        ->toThrow(AuthorizationException::class);
});

test('moderadora desbloqueia o próprio bloqueio, com motivo obrigatório', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $block = DelasRequesterBlock::factory()->create(['blocked_by' => $moderator->getKey()]);

    expect(fn () => resolve(UnblockDelasRequester::class)->handle($block, $moderator, ''))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));

    resolve(UnblockDelasRequester::class)->handle($block, $moderator, 'Situação esclarecida.');

    $block->refresh();

    expect($block->isActive())->toBeFalse()
        ->and($block->lifted_by)->toBe($moderator->getKey())
        ->and($block->lift_reason)->toBe('Situação esclarecida.')
        ->and(DelasTransition::query()->sole()->action)->toBe(DelasAction::Unblocked);
});

test('moderadora não desbloqueia o bloqueio de outra; líder desbloqueia qualquer um', function (): void {
    $block = DelasRequesterBlock::factory()->create();

    expect(fn () => resolve(UnblockDelasRequester::class)->handle($block, User::factory()->delasModerator()->create(), 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_unblock_others'));

    resolve(UnblockDelasRequester::class)->handle($block, User::factory()->delasLead()->create(), 'motivo');

    expect($block->fresh()->isActive())->toBeFalse();
});

test('um bloqueio encerrado não é encerrado de novo', function (): void {
    $lead = User::factory()->delasLead()->create();
    $block = DelasRequesterBlock::factory()->lifted()->create();

    expect(fn () => resolve(UnblockDelasRequester::class)->handle($block, $lead, 'de novo'))
        ->toThrow(DelasException::class, __('delas::exceptions.block_already_lifted'));
});
