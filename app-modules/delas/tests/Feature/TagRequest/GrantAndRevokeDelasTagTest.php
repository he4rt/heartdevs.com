<?php

declare(strict_types=1);

use He4rt\Delas\Block\Events\DelasRequesterUnblocked;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\GrantDelasTag;
use He4rt\Delas\TagRequest\Actions\RevokeDelasTag;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagGranted;
use He4rt\Delas\TagRequest\Events\DelasTagRevoked;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Delas\Team\Actions\RemoveDelasModerator;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->grant = resolve(GrantDelasTag::class);
    $this->revoke = resolve(RevokeDelasTag::class);
    $this->eligibility = resolve(DelasEligibility::class);
});

// Conceder

test('líder concede a quem não tem solicitação: cria uma já aprovada', function (): void {
    Event::fake([DelasTagGranted::class]);
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->create();

    $request = $this->grant->handle($target, $lead);

    $transition = DelasTransition::query()->sole();
    expect($request->status)->toBe(DelasRequestStatus::Approved);
    expect(DelasTagRequest::query()->count())->toBe(1);
    expect($transition->action)->toBe(DelasAction::Granted);
    expect($transition->triggered_by)->toBe(DelasTriggeredBy::Lead);
    Event::assertDispatched(DelasTagGranted::class);
});

test('conceder com solicitação pendente aprova essa solicitação', function (): void {
    $lead = User::factory()->delasLead()->create();
    $pending = DelasTagRequest::factory()->pending()->create();

    $request = $this->grant->handle($pending->user, $lead);

    expect($request->is($pending))->toBeTrue();
    expect($pending->fresh()->status)->toBe(DelasRequestStatus::Approved);
    expect(DelasTagRequest::query()->count())->toBe(1);
});

test('conceder ignora a espera', function (): void {
    $lead = User::factory()->delasLead()->create();
    $rejected = DelasTagRequest::factory()->rejected()->create();

    $this->grant->handle($rejected->user, $lead);

    expect($this->eligibility->hasTag($rejected->user))->toBeTrue();
});

test('conceder a quem está bloqueada exige motivo', function (): void {
    $lead = User::factory()->delasLead()->create();
    $block = DelasRequesterBlock::factory()->create();

    expect(fn () => $this->grant->handle($block->user, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('conceder a quem está bloqueada, com motivo, encerra o bloqueio', function (): void {
    Event::fake([DelasTagGranted::class, DelasRequesterUnblocked::class]);
    $lead = User::factory()->delasLead()->create();
    $block = DelasRequesterBlock::factory()->create();

    $this->grant->handle($block->user, $lead, 'Esclarecido com o comitê.');

    $block->refresh();
    expect($block->isActive())->toBeFalse();
    expect($block->lift_reason)->toBe('Esclarecido com o comitê.');
    expect(DelasTransition::query()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Unblocked, DelasAction::Granted]);
    Event::assertDispatched(DelasRequesterUnblocked::class);
    Event::assertDispatched(DelasTagGranted::class);
});

test('não concede a quem já tem a tag', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => $this->grant->handle($approved->user, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.already_has_tag'));
});

test('líder não concede a tag a si mesma', function (): void {
    $lead = User::factory()->delasLead()->create();

    expect(fn () => $this->grant->handle($lead, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'));
});

test('moderadora não concede a tag direto', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $target = User::factory()->create();

    expect(fn () => $this->grant->handle($target, $moderator))
        ->toThrow(AuthorizationException::class);
});

// Remover

test('moderadora não remove a tag direto', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => $this->revoke->handle($approved->user, $moderator, 'motivo'))
        ->toThrow(AuthorizationException::class);
});

test('remover a tag exige motivo', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => $this->revoke->handle($approved->user, $admin, reason: null))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
});

test('remover a tag não apaga o registro e abre a espera', function (): void {
    Event::fake([DelasTagRevoked::class]);
    $admin = User::factory()->superAdmin()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    $this->revoke->handle($approved->user, $admin, 'A pessoa pediu a remoção.');

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
    expect($this->eligibility->for($approved->user)->state)->toBe(DelasEligibilityState::Cooldown);
    expect(DelasTransition::query()->sole()->triggered_by)->toBe(DelasTriggeredBy::Admin);
    Event::assertDispatched(DelasTagRevoked::class);
});

test('não remove de quem não tem a tag', function (): void {
    $lead = User::factory()->delasLead()->create();
    $withoutTag = User::factory()->create();

    expect(fn () => $this->revoke->handle($withoutTag, $lead, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.has_no_tag'));
});

// Remover e bloquear

test('remover a tag e bloquear acontecem juntos, com o mesmo motivo', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    $this->revoke->handle($approved->user, $lead, 'Conta criada só para conseguir a tag.', alsoBlock: true);

    $block = DelasRequesterBlock::query()->active()->where('user_id', $approved->user_id)->sole();
    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
    expect($block->reason)->toBe('Conta criada só para conseguir a tag.');
    expect($block->blocked_by)->toBe($lead->getKey());
    expect($this->eligibility->for($approved->user)->state)->toBe(DelasEligibilityState::Blocked);
    expect(DelasTransition::query()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Revoked, DelasAction::Blocked]);
});

test('se não der para bloquear, a tag também não é removida', function (): void {
    $lead = User::factory()->delasLead()->create();
    // Super admin passa no `moderate-delas` pelo Gate::before, então nunca pode ser bloqueado.
    $superAdminWithTag = User::factory()->superAdmin()->create();
    $approved = DelasTagRequest::factory()->for($superAdminWithTag)->approved()->create();

    expect(fn () => $this->revoke->handle($superAdminWithTag, $lead, 'Motivo.', alsoBlock: true))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_block_team'));

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Approved);
    expect(DelasTransition::query()->count())->toBe(0);
});

test('quem já está bloqueada só perde a tag, sem segundo bloqueio', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();
    DelasRequesterBlock::factory()->for($approved->user)->create();

    $this->revoke->handle($approved->user, $lead, 'Motivo.', alsoBlock: true);

    expect(DelasRequesterBlock::query()->where('user_id', $approved->user_id)->count())->toBe(1);
    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});

// Quem é da equipe não perde a tag

test('não remove a tag de quem é da equipe', function (string $role): void {
    $lead = User::factory()->delasLead()->create();
    $teamMember = User::factory()->{$role}()->create();
    $approved = DelasTagRequest::factory()->for($teamMember)->approved()->create();

    expect(fn () => $this->revoke->handle($teamMember, $lead, 'Motivo.'))
        ->toThrow(DelasException::class, __('delas::exceptions.team_member_keeps_tag'));

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Approved);
})->with(['moderadora' => 'delasModerator', 'líder' => 'delasLead']);

test('depois de sair da moderação, a pessoa pode perder a tag', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderator = User::factory()->delasModerator()->create();
    $approved = DelasTagRequest::factory()->for($moderator)->approved()->create();

    resolve(RemoveDelasModerator::class)->handle($moderator, $lead);
    $this->revoke->handle($moderator->fresh(), $lead, 'Saiu da He4rt Delas.');

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});
