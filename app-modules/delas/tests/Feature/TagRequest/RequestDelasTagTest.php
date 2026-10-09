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

beforeEach(function (): void {
    $this->requestTag = resolve(RequestDelasTag::class);
    $this->eligibility = resolve(DelasEligibility::class);
});

// Pedir

test('registra a solicitação como pendente e avisa', function (): void {
    Event::fake([DelasTagRequested::class]);
    $user = User::factory()->withDiscord()->create();

    $request = $this->requestTag->handle($user);

    expect($request->status)->toBe(DelasRequestStatus::Pending);
    expect($this->eligibility->for($user)->state)->toBe(DelasEligibilityState::Pending);
    Event::assertDispatched(fn (DelasTagRequested $event): bool => $event->requestId === $request->getKey());
});

test('o pedido fica no histórico como ação da própria pessoa', function (): void {
    $user = User::factory()->withDiscord()->create();

    $this->requestTag->handle($user);

    $transition = DelasTransition::query()->sole();
    expect($transition->action)->toBe(DelasAction::Requested);
    expect($transition->triggered_by)->toBe(DelasTriggeredBy::User);
    expect($transition->actor_id)->toBe($user->getKey());
});

test('recusa quem já tem uma solicitação pendente ou aprovada', function (string $state): void {
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->{$state}()->create();

    expect(fn () => $this->requestTag->handle($user))
        ->toThrow(DelasException::class, __('delas::exceptions.already_has_active_request'));
})->with(['pending', 'approved']);

test('recusa quem está bloqueada', function (): void {
    $user = User::factory()->create();
    DelasRequesterBlock::factory()->for($user)->create();

    expect(fn () => $this->requestTag->handle($user))
        ->toThrow(DelasException::class, __('delas::exceptions.blocked'));
});

test('quem tem a tag continua com ela mesmo bloqueada', function (): void {
    $user = User::factory()->create();
    DelasTagRequest::factory()->for($user)->approved()->create();
    DelasRequesterBlock::factory()->for($user)->create();

    expect($this->eligibility->for($user)->hasTag())->toBeTrue();
});

// Espera depois de rejeição ou remoção

test('recusa o pedido antes de completar 15 dias da rejeição ou remoção', function (string $state): void {
    $this->travelTo(today());
    $user = User::factory()->withDiscord()->create();
    DelasTagRequest::factory()->for($user)->{$state}()->create(['decided_at' => now()]);

    $this->travelTo(now()->addDays(15)->subMinute());

    expect(fn () => $this->requestTag->handle($user))->toThrow(DelasException::class);
})->with(['rejected', 'revoked']);

test('aceita o pedido passados 15 dias da rejeição ou remoção', function (string $state): void {
    $this->travelTo(today());
    $user = User::factory()->withDiscord()->create();
    DelasTagRequest::factory()->for($user)->{$state}()->create(['decided_at' => now()]);

    $this->travelTo(now()->addDays(15)->addMinute());

    expect($this->requestTag->handle($user)->status)->toBe(DelasRequestStatus::Pending);
})->with(['rejected', 'revoked']);

test('a espera vem da config', function (): void {
    config(['delas.request_cooldown_days' => 3]);
    $user = User::factory()->withDiscord()->create();
    DelasTagRequest::factory()->for($user)->rejected()->create(['decided_at' => now()->subDays(4)]);

    expect($this->eligibility->for($user)->canRequest())->toBeTrue();
});

// Discord

test('sem o Discord conectado, a pessoa aparece como precisando conectar', function (): void {
    $user = User::factory()->create();

    expect($this->eligibility->for($user)->state)->toBe(DelasEligibilityState::DiscordRequired);
});

test('sem o Discord conectado, não dá para pedir', function (): void {
    $user = User::factory()->create();

    expect(fn () => $this->requestTag->handle($user))
        ->toThrow(DelasException::class, __('delas::exceptions.discord_required'));
});

test('um Discord desconectado não conta', function (): void {
    $user = User::factory()->withDiscord()->create();

    $user->providers()->update(['disconnected_at' => now()]);

    expect($this->eligibility->for($user)->state)->toBe(DelasEligibilityState::DiscordRequired);
});

test('a exigência do Discord vem da config', function (): void {
    config(['delas.require_discord' => false]);
    $userWithoutDiscord = User::factory()->create();

    expect($this->eligibility->for($userWithoutDiscord)->canRequest())->toBeTrue();
});

test('o bloqueio aparece antes de faltar o Discord', function (): void {
    $blocked = User::factory()->create();
    DelasRequesterBlock::factory()->for($blocked)->create();

    expect($this->eligibility->for($blocked)->state)->toBe(DelasEligibilityState::Blocked);
});

test('a espera aparece antes de faltar o Discord', function (): void {
    $waiting = User::factory()->create();
    DelasTagRequest::factory()->for($waiting)->rejected()->create(['decided_at' => now()]);

    expect($this->eligibility->for($waiting)->state)->toBe(DelasEligibilityState::Cooldown);
});
