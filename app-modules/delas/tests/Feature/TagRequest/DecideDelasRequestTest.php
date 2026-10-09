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

beforeEach(function (): void {
    $this->approve = resolve(ApproveDelasRequest::class);
    $this->reject = resolve(RejectDelasRequest::class);
    $this->eligibility = resolve(DelasEligibility::class);
});

// Aprovar

test('moderadora aprova: a solicitação guarda quem decidiu e quando', function (): void {
    Event::fake([DelasTagApproved::class]);
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();
    $this->freezeSecond();

    $this->approve->handle($request, $moderator);

    $request->refresh();
    expect($request->status)->toBe(DelasRequestStatus::Approved);
    expect($request->decided_by)->toBe($moderator->getKey());
    expect($request->decided_at->toDateTimeString())->toBe(now()->toDateTimeString());
    Event::assertDispatched(DelasTagApproved::class);
});

test('aprovada, a pessoa passa a ter a tag', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->approve->handle($request, $moderator);

    expect($this->eligibility->hasTag($request->user))->toBeTrue();
});

test('aprovar registra no histórico a moderadora e a mudança de situação', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->approve->handle($request, $moderator);

    $transition = DelasTransition::query()->sole();
    expect($transition->action)->toBe(DelasAction::Approved);
    expect($transition->triggered_by)->toBe(DelasTriggeredBy::Moderator);
    expect($transition->from_status)->toBe(DelasRequestStatus::Pending);
    expect($transition->to_status)->toBe(DelasRequestStatus::Approved);
});

test('super admin decide sem papel da He4rt Delas e fica registrado como admin', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->approve->handle($request, $admin);

    expect(DelasTransition::query()->sole()->triggered_by)->toBe(DelasTriggeredBy::Admin);
});

// Rejeitar

test('moderadora rejeita com motivo: a solicitação sai das pendentes e guarda o motivo limpo', function (): void {
    Event::fake([DelasTagRejected::class]);
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->reject->handle($request, $moderator, '  Perfil sem informações.  ');

    $request->refresh();
    expect($request->status)->toBe(DelasRequestStatus::Rejected);
    expect($request->decision_reason)->toBe('Perfil sem informações.');
    expect(DelasTagRequest::query()->pending()->exists())->toBeFalse();
    expect(DelasTransition::query()->sole()->reason)->toBe('Perfil sem informações.');
    Event::assertDispatched(DelasTagRejected::class);
});

test('rejeitada, a pessoa não tem a tag', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    $this->reject->handle($request, $moderator, 'Perfil sem informações.');

    expect($this->eligibility->hasTag($request->user))->toBeFalse();
});

test('rejeitar sem motivo é recusado', function (?string $reason): void {
    $moderator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    expect(fn () => $this->reject->handle($request, $moderator, $reason))
        ->toThrow(DelasException::class, __('delas::exceptions.reason_required'));
})->with([null, '', '   ']);

// Quem pode decidir

test('quem não é da equipe não aprova', function (): void {
    $member = User::factory()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    expect(fn () => $this->approve->handle($request, $member))
        ->toThrow(AuthorizationException::class);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

test('quem não é da equipe não rejeita', function (): void {
    $member = User::factory()->create();
    $request = DelasTagRequest::factory()->pending()->create();

    expect(fn () => $this->reject->handle($request, $member, 'motivo'))
        ->toThrow(AuthorizationException::class);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Pending);
});

test('moderadora não aprova a própria solicitação', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $ownRequest = DelasTagRequest::factory()->for($moderator)->pending()->create();

    expect(fn () => $this->approve->handle($ownRequest, $moderator))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'));
});

test('moderadora não rejeita a própria solicitação', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $ownRequest = DelasTagRequest::factory()->for($moderator)->pending()->create();

    expect(fn () => $this->reject->handle($ownRequest, $moderator, 'motivo'))
        ->toThrow(DelasException::class, __('delas::exceptions.cannot_decide_own'));
});

test('uma solicitação já decidida não é decidida de novo', function (): void {
    $moderator = User::factory()->delasModerator()->create();
    $otherModerator = User::factory()->delasModerator()->create();
    $request = DelasTagRequest::factory()->pending()->create();
    $this->approve->handle($request, $moderator);

    expect(fn () => $this->reject->handle($request, $otherModerator, 'atrasada'))
        ->toThrow(DelasException::class);

    expect($request->fresh()->status)->toBe(DelasRequestStatus::Approved);
    expect(DelasTransition::query()->count())->toBe(1);
});
