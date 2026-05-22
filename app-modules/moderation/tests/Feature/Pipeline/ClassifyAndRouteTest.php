<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\RouteCaseAction;
use He4rt\Moderation\Classification\Jobs\ClassifyAndRoute;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

test('enriches case with AI scores and calls RouteCaseAction', function (): void {
    Event::fake([CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => true,
                'categories' => [],
                'category_scores' => [
                    'harassment' => 0.8,
                    'hate' => 0.3,
                ],
            ]],
        ]),
    ]);

    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'some toxic content here'],
        'ai_scores' => ['spam' => 0.95],
        'violation_type' => 'spam',
        'severity' => 'high',
        'classifier_version' => 'rules',
        'suggested_action' => 'ban',
        'author_id' => $user->id,
        'status' => 'pending',
        'priority' => 50,
    ]);

    $job = new ClassifyAndRoute($case);
    $job->handle(resolve(RouteCaseAction::class));

    $case->refresh();

    // AI scores should be merged (harassment from OpenAI added)
    expect($case->ai_scores)->toHaveKey('harassment')
        ->and($case->ai_scores)->toHaveKey('spam')
        ->and($case->priority)->toBeGreaterThan(50);

    Event::assertDispatched(CaseQueued::class);
});

test('emits CaseReadyForEnforcement when classifier_version is rules and suggested_action is set', function (): void {
    Event::fake([CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => false,
                'categories' => [],
                'category_scores' => ['harassment' => 0.1],
            ]],
        ]),
    ]);

    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'buy followers now'],
        'ai_scores' => ['spam' => 0.95],
        'violation_type' => 'spam',
        'severity' => 'high',
        'classifier_version' => 'rules',
        'suggested_action' => 'ban',
        'author_id' => $user->id,
        'status' => 'pending',
        'priority' => 50,
    ]);

    $job = new ClassifyAndRoute($case);
    $job->handle(resolve(RouteCaseAction::class));

    Event::assertDispatched(CaseReadyForEnforcement::class);
});

test('does NOT emit CaseReadyForEnforcement when classifier_version is aggregate', function (): void {
    Event::fake([CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => true,
                'categories' => [],
                'category_scores' => ['harassment' => 0.9],
            ]],
        ]),
    ]);

    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'toxic content here'],
        'ai_scores' => ['harassment' => 0.9],
        'violation_type' => 'harassment',
        'severity' => 'high',
        'classifier_version' => 'aggregate',
        'suggested_action' => 'ban',
        'author_id' => $user->id,
        'status' => 'pending',
        'priority' => 50,
    ]);

    $job = new ClassifyAndRoute($case);
    $job->handle(resolve(RouteCaseAction::class));

    Event::assertNotDispatched(CaseReadyForEnforcement::class);
});
