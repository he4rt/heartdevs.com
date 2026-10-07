<?php

declare(strict_types=1);

use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Actions\RequestDelasTag;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagRequested;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event;

test('registra a solicitação como pendente e grava o histórico', function (): void {
    Event::fake([DelasTagRequested::class]);
    $user = User::factory()->create();

    $request = resolve(RequestDelasTag::class)->handle($user);

    $transition = DelasTransition::query()->sole();

    expect($request->status)->toBe(DelasRequestStatus::Pending)
        ->and($transition->action)->toBe(DelasAction::Requested)
        ->and($transition->triggered_by)->toBe(DelasTriggeredBy::User)
        ->and($transition->actor_id)->toBe($user->getKey())
        ->and(resolve(DelasEligibility::class)->for($user)->state)->toBe(DelasEligibilityState::Pending);

    Event::assertDispatched(fn (DelasTagRequested $event): bool => $event->requestId === $request->getKey());
});

test('recusa quem já tem uma solicitação pendente ou aprovada', function (string $state): void {
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->{$state}()->create();

    expect(fn () => resolve(RequestDelasTag::class)->handle($user))
        ->toThrow(DelasException::class, __('delas::exceptions.already_has_active_request'));
})->with(['pending', 'approved']);

test('recusa quem está bloqueada', function (): void {
    $user = User::factory()->create();
    DelasRequesterBlock::factory()->for($user)->create();

    expect(fn () => resolve(RequestDelasTag::class)->handle($user))
        ->toThrow(DelasException::class, __('delas::exceptions.blocked'));
});

test('respeita a espera de 15 dias depois de uma rejeição ou remoção', function (string $state): void {
    $this->travelTo(today());
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->{$state}()->create(['decided_at' => now()]);

    $this->travelTo(now()->addDays(15)->subMinute());
    expect(fn () => resolve(RequestDelasTag::class)->handle($user))->toThrow(DelasException::class);

    $this->travelTo(now()->addMinutes(2));
    expect(resolve(RequestDelasTag::class)->handle($user)->status)->toBe(DelasRequestStatus::Pending);
})->with(['rejected', 'revoked']);

test('a espera vem da config', function (): void {
    config(['delas.request_cooldown_days' => 3]);
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->rejected()->create(['decided_at' => now()->subDays(4)]);

    expect(resolve(DelasEligibility::class)->for($user)->canRequest())->toBeTrue();
});

test('quem tem a tag continua com ela mesmo bloqueada', function (): void {
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->approved()->create();
    DelasRequesterBlock::factory()->for($user)->create();

    expect(resolve(DelasEligibility::class)->for($user)->hasTag())->toBeTrue();
});
