<?php

declare(strict_types=1);

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\ApproveDelasRequest;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagApproved;
use He4rt\Delas\TagRequest\Events\DelasTagRejected;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

test('moderadora aprova: a tag aparece e o histórico guarda quem e quando', function (): void {
    Event::fake([DelasTagApproved::class]);
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->freezeSecond();
    resolve(ApproveDelasRequest::class)->handle($request, $moderator);

    $request->refresh();
    $transition = DelasTransition::query()->sole();

    expect($request->status)->toBe(DelasRequestStatus::Approved)
        ->and($request->decided_by)->toBe($moderator->getKey())
        ->and($request->decided_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and(resolve(DelasEligibility::class)->hasTag($request->user))->toBeTrue()
        ->and($transition->action)->toBe(DelasAction::Approved)
        ->and($transition->triggered_by)->toBe(DelasTriggeredBy::Moderator)
        ->and($transition->from_status)->toBe(DelasRequestStatus::Pending)
        ->and($transition->to_status)->toBe(DelasRequestStatus::Approved);

    Event::assertDispatched(DelasTagApproved::class);
});

test('moderadora rejeita com motivo: a tag não aparece e a solicitação sai das pendentes', function (): void {
    Event::fake([DelasTagRejected::class]);
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    resolve(RejectDelasRequest::class)->handle($request, $moderator, '  Perfil sem informações.  ');

    $request->refresh();

    expect($request->status)->toBe(DelasRequestStatus::Rejected)
        ->and($request->decision_reason)->toBe('Perfil sem informações.')
        ->and(DelasTagRequest::query()->pending()->exists())->toBeFalse()
        ->and(resolve(DelasEligibility::class)->hasTag($request->user))->toBeFalse()
        ->and(DelasTransition::query()->sole()->reason)->toBe('Perfil sem informações.');

    Event::assertDispatched(DelasTagRejected::class);
});

test('rejeitar sem motivo é recusado', function (?string $reason): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    expect(fn () => resolve(RejectDelasRequest::class)->handle($request, $moderator, $reason))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
})->with([null, '', '   ']);

test('quem não é da equipe não aprova nem rejeita', function (): void {
    $member = User::factory()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    expect(fn () => resolve(ApproveDelasRequest::class)->handle($request, $member))->toThrow(AuthorizationException::class)
        ->and(fn () => resolve(RejectDelasRequest::class)->handle($request, $member, 'motivo'))->toThrow(AuthorizationException::class)
        ->and($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

test('moderadora não decide a própria solicitação', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->for($moderator)->pending()->create();

    expect(fn () => resolve(ApproveDelasRequest::class)->handle($request, $moderator))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'))
        ->and(fn () => resolve(RejectDelasRequest::class)->handle($request, $moderator, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'));
});

test('uma solicitação já decidida não é decidida de novo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $other = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    resolve(ApproveDelasRequest::class)->handle($request, $moderator);

    expect(fn () => resolve(RejectDelasRequest::class)->handle($request, $other, 'atrasada'))
        ->toThrow(DelasException::class)
        ->and($request->fresh()->status)->toBe(DelasRequestStatus::Approved)
        ->and(DelasTransition::query()->count())->toBe(1);
});

test('super admin decide sem papel da He4rt Delas e fica registrado como admin', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    resolve(ApproveDelasRequest::class)->handle($request, $admin);

    expect(DelasTransition::query()->sole()->triggered_by)->toBe(DelasTriggeredBy::Admin);
});
