<?php

declare(strict_types=1);

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Actions\AddDelasModerator;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function (): void {
    $this->addModerator = resolve(AddDelasModerator::class);
    $this->removeModerator = resolve(RemoveDelasModerator::class);
});

// Adicionar

test('líder adiciona moderadora e a ação vai para o histórico', function (): void {
    $lead = User::factory()->delasLead()->create();
    $target = DelasTagRequest::factory()->approved()->create(['decided_by' => $lead->getKey()])->user;

    $this->addModerator->handle($target, $lead);

    expect($target->fresh()->can('moderate-delas'))->toBeTrue();
    expect(DelasTransition::query()->pluck('action')->all())->toBe([DelasAction::ModeratorAdded]);
});

test('só quem tem a tag vira moderadora', function (): void {
    $lead = User::factory()->delasLead()->create();
    $withoutTag = User::factory()->create();

    expect(fn () => $this->addModerator->handle($withoutTag, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.moderator_needs_tag'));

    expect($withoutTag->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
});

test('não adiciona quem já é moderadora', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderator = User::factory()->delasModerator()->create();

    expect(fn () => $this->addModerator->handle($moderator, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.already_moderator'));
});

test('moderadora não gerencia a equipe', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();

    expect(fn () => $this->addModerator->handle($target, $moderator))
        ->toThrow(AuthorizationException::class);
});

test('líder não transforma líder nem super admin em moderadora', function (string $state): void {
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->{$state}()->create();

    expect(fn () => $this->addModerator->handle($target, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_manage_role'));
})->with(['delasLead', 'superAdmin']);

// Remover

test('líder remove moderadora e a ação vai para o histórico', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderator = User::factory()->delasModerator()->create();
    DelasTagRequest::factory()->for($moderator)->approved()->create(['decided_by' => $lead->getKey()]);

    $this->removeModerator->handle($moderator, $lead);

    expect($moderator->fresh()->can('moderate-delas'))->toBeFalse();
    expect(DelasTransition::query()->pluck('action')->all())->toBe([DelasAction::ModeratorRemoved]);
});

test('não remove quem não é moderadora', function (): void {
    $lead = User::factory()->delasLead()->create();
    $member = User::factory()->create();

    expect(fn () => $this->removeModerator->handle($member, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.not_moderator'));
});

test('líder não remove líder nem super admin', function (string $state): void {
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->{$state}()->create();

    expect(fn () => $this->removeModerator->handle($target, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_manage_role'));
})->with(['delasLead', 'superAdmin']);

test('a moderadora removida mantém as decisões no histórico', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderator = User::factory()->delasModerator()->create();

    $this->removeModerator->handle($moderator, $lead);

    expect($moderator->fresh()->hasRole(UserRole::DelasModerator))->toBeFalse();
    expect(DelasTransition::query()->where('user_id', $moderator->getKey())->exists())->toBeTrue();
});
