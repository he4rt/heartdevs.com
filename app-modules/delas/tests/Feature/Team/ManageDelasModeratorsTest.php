<?php

declare(strict_types=1);

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('líder adiciona e remove moderadora, e as duas ações vão para o histórico', function (): void {
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->create();

    resolve(AddDelasModerator::class)->handle($target, $lead);
    expect($target->fresh()->can('moderate-delas'))->toBeTrue();

    resolve(RemoveDelasModerator::class)->handle($target->fresh(), $lead);
    expect($target->fresh()->can('moderate-delas'))->toBeFalse()
        ->and(DelasTransition::query()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::ModeratorAdded, DelasAction::ModeratorRemoved]);
});

test('moderadora não gerencia a equipe', function (): void {
    $moderator = User::factory()->delasModerator()->create();

    expect(fn () => resolve(AddDelasModerator::class)->handle(User::factory()->create(), $moderator))
        ->toThrow(AuthorizationException::class);
});

test('líder não mexe em líderes nem em super admins', function (string $state): void {
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->{$state}()->create();

    expect(fn () => resolve(AddDelasModerator::class)->handle($target, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_manage_role'))
        ->and(fn () => resolve(RemoveDelasModerator::class)->handle($target, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_manage_role'));
})->with(['delasLead', 'superAdmin']);

test('não adiciona quem já é moderadora nem remove quem não é', function (): void {
    $lead = User::factory()->delasLead()->create();

    expect(fn () => resolve(AddDelasModerator::class)->handle(User::factory()->delasModerator()->create(), $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.already_moderator'))
        ->and(fn () => resolve(RemoveDelasModerator::class)->handle(User::factory()->create(), $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.not_moderator'));
});

test('a moderadora removida mantém as decisões no histórico', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderator = User::factory()->delasModerator()->create();

    resolve(RemoveDelasModerator::class)->handle($moderator, $lead);

    expect($moderator->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse()
        ->and(DelasTransition::query()->where('user_id', $moderator->getKey())->exists())->toBeTrue();
});
