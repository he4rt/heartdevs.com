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
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

test('líder concede a quem não tem solicitação: cria uma já aprovada', function (): void {
    Event::fake([DelasTagGranted::class]);
    $lead = User::factory()->delasLead()->create();
    $target = User::factory()->create();

    $request = resolve(GrantDelasTag::class)->handle($target, $lead);

    expect($request->status)->toBe(DelasRequestStatus::Approved)
        ->and(DelasTagRequest::query()->count())->toBe(1)
        ->and(DelasTransition::query()->sole()->action)->toBe(DelasAction::Granted)
        ->and(DelasTransition::query()->sole()->triggered_by)->toBe(DelasTriggeredBy::Lead);

    Event::assertDispatched(DelasTagGranted::class);
});

test('conceder com solicitação pendente aprova essa solicitação', function (): void {
    $lead = User::factory()->delasLead()->create();
    $pending = DelasTagRequest::factory()->pending()->create();

    $request = resolve(GrantDelasTag::class)->handle($pending->user, $lead);

    expect($request->is($pending))->toBeTrue()
        ->and($pending->fresh()->status)->toBe(DelasRequestStatus::Approved)
        ->and(DelasTagRequest::query()->count())->toBe(1);
});

test('conceder ignora a espera', function (): void {
    $lead = User::factory()->delasLead()->create();
    $rejected = DelasTagRequest::factory()->rejected()->create();

    resolve(GrantDelasTag::class)->handle($rejected->user, $lead);

    expect(resolve(DelasEligibility::class)->hasTag($rejected->user))->toBeTrue();
});

test('conceder a quem está bloqueada exige motivo e encerra o bloqueio', function (): void {
    Event::fake([DelasTagGranted::class, DelasRequesterUnblocked::class]);
    $lead = User::factory()->delasLead()->create();
    $block = DelasRequesterBlock::factory()->create();

    expect(fn () => resolve(GrantDelasTag::class)->handle($block->user, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));

    resolve(GrantDelasTag::class)->handle($block->user, $lead, 'Esclarecido com o comitê.');

    expect($block->fresh()->isActive())->toBeFalse()
        ->and($block->fresh()->lift_reason)->toBe('Esclarecido com o comitê.')
        ->and(DelasTransition::query()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Unblocked, DelasAction::Granted]);

    Event::assertDispatched(DelasRequesterUnblocked::class);
    Event::assertDispatched(DelasTagGranted::class);
});

test('não concede a quem já tem a tag nem a si mesma', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => resolve(GrantDelasTag::class)->handle($approved->user, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.already_has_tag'))
        ->and(fn () => resolve(GrantDelasTag::class)->handle($lead, $lead))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'));
});

test('moderadora não concede nem remove a tag direto', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => resolve(GrantDelasTag::class)->handle(User::factory()->create(), $moderator))->toThrow(AuthorizationException::class)
        ->and(fn () => resolve(RevokeDelasTag::class)->handle($approved->user, $moderator, 'motivo'))->toThrow(AuthorizationException::class);
});

test('remover a tag exige motivo, não apaga o registro e abre a espera', function (): void {
    Event::fake([DelasTagRevoked::class]);
    $admin = User::factory()->superAdmin()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    expect(fn () => resolve(RevokeDelasTag::class)->handle($approved->user, $admin, reason: null))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));

    resolve(RevokeDelasTag::class)->handle($approved->user, $admin, 'A pessoa pediu a remoção.');

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked)
        ->and(resolve(DelasEligibility::class)->for($approved->user)->state)->toBe(DelasEligibilityState::Cooldown)
        ->and(DelasTransition::query()->sole()->triggered_by)->toBe(DelasTriggeredBy::Admin);

    Event::assertDispatched(DelasTagRevoked::class);
});

test('não remove de quem não tem a tag', function (): void {
    $lead = User::factory()->delasLead()->create();

    expect(fn () => resolve(RevokeDelasTag::class)->handle(User::factory()->create(), $lead, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.has_no_tag'));
});

test('remover a tag e bloquear acontecem juntos, com o mesmo motivo', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();

    resolve(RevokeDelasTag::class)->handle($approved->user, $lead, 'Conta criada só para conseguir a tag.', alsoBlock: true);

    $block = DelasRequesterBlock::query()->active()->where('user_id', $approved->user_id)->sole();

    expect($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked)
        ->and($block->reason)->toBe('Conta criada só para conseguir a tag.')
        ->and($block->blocked_by)->toBe($lead->getKey())
        ->and(resolve(DelasEligibility::class)->for($approved->user)->state)->toBe(DelasEligibilityState::Blocked)
        ->and(DelasTransition::query()->pluck('action')->all())
        ->toEqualCanonicalizing([DelasAction::Revoked, DelasAction::Blocked]);
});

test('se não der para bloquear, a tag também não é removida', function (): void {
    $lead = User::factory()->delasLead()->create();
    $moderatorWithTag = User::factory()->delasModerator()->create();
    $approved = DelasTagRequest::factory()->for($moderatorWithTag)->approved()->create();

    expect(fn () => resolve(RevokeDelasTag::class)->handle($moderatorWithTag, $lead, 'Motivo.', alsoBlock: true))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_block_team'))
        ->and($approved->fresh()->status)->toBe(DelasRequestStatus::Approved)
        ->and(DelasTransition::query()->count())->toBe(0);
});

test('quem já está bloqueada só perde a tag, sem segundo bloqueio', function (): void {
    $lead = User::factory()->delasLead()->create();
    $approved = DelasTagRequest::factory()->approved()->create();
    DelasRequesterBlock::factory()->for($approved->user)->create();

    resolve(RevokeDelasTag::class)->handle($approved->user, $lead, 'Motivo.', alsoBlock: true);

    expect(DelasRequesterBlock::query()->where('user_id', $approved->user_id)->count())->toBe(1)
        ->and($approved->fresh()->status)->toBe(DelasRequestStatus::Revoked);
});
