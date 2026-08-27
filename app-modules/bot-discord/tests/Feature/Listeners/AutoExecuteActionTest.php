<?php

declare(strict_types=1);

use He4rt\BotDiscord\Listeners\AutoExecuteAction;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use Illuminate\Support\Facades\Bus;

test('creates action and dispatches ExecuteAction for discord case', function (): void {
    Bus::fake([ExecuteAction::class]);

    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'source_platform' => Platform::Discord,
        'suggested_action' => ActionType::Mute,
        'author_id' => $user->id,
    ]);

    $listener = new AutoExecuteAction();
    $listener->handle(new CaseReadyForEnforcement($case));

    $action = ModerationAction::query()->where('case_id', $case->id)->first();

    expect($action)->not->toBeNull()
        ->and($action->action_type)->toBe(ActionType::Mute)
        ->and($action->automated)->toBeTrue()
        ->and($action->moderator_id)->toBeNull()
        ->and($action->duration)->toBe('24h')
        ->and($action->target_platforms)->toBe([Platform::Discord->value]);

    Bus::assertDispatched(ExecuteAction::class);
});

test('resolves permanent duration for ban action', function (): void {
    Bus::fake([ExecuteAction::class]);

    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'source_platform' => Platform::Discord,
        'suggested_action' => ActionType::Ban,
        'author_id' => $user->id,
    ]);

    $listener = new AutoExecuteAction();
    $listener->handle(new CaseReadyForEnforcement($case));

    $action = ModerationAction::query()->where('case_id', $case->id)->first();

    expect($action->duration)->toBe('permanent');
});

test('does nothing when platform is not discord', function (): void {
    Bus::fake([ExecuteAction::class]);

    $case = ModerationCase::factory()->create([
        'source_platform' => Platform::Twitch,
        'suggested_action' => ActionType::Ban,
    ]);

    $listener = new AutoExecuteAction();
    $listener->handle(new CaseReadyForEnforcement($case));

    expect(ModerationAction::query()->where('case_id', $case->id)->exists())->toBeFalse();

    Bus::assertNotDispatched(ExecuteAction::class);
});

test('does nothing when author_id is null', function (): void {
    Bus::fake([ExecuteAction::class]);

    $case = ModerationCase::factory()->create([
        'source_platform' => Platform::Discord,
        'suggested_action' => ActionType::Mute,
        'author_id' => null,
    ]);

    $listener = new AutoExecuteAction();
    $listener->handle(new CaseReadyForEnforcement($case));

    expect(ModerationAction::query()->where('case_id', $case->id)->exists())->toBeFalse();

    Bus::assertNotDispatched(ExecuteAction::class);
});
