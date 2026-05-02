<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Cases\Models\ModerationReport;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ModerationCase has reports', function (): void {
    $case = ModerationCase::factory()->create();
    $report = ModerationReport::factory()->create(['case_id' => $case->id]);

    expect($case->reports)->toHaveCount(1)
        ->and($case->reports->first()->id)->toBe($report->id);
});

test('ModerationCase has actions', function (): void {
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create(['case_id' => $case->id]);

    expect($case->actions)->toHaveCount(1)
        ->and($case->actions->first()->id)->toBe($action->id);
});

test('ModerationCase belongs to author', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);

    expect($case->author->id)->toBe($user->id);
});

test('ModerationCase belongs to assignee', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create(['assigned_to' => $moderator->id]);

    expect($case->assignee->id)->toBe($moderator->id);
});

test('ModerationCase belongs to tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $case = ModerationCase::factory()->create(['tenant_id' => $tenant->id]);

    expect($case->tenant->id)->toBe($tenant->id);
});

test('ModerationAction belongs to case', function (): void {
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create(['case_id' => $case->id]);

    expect($action->case->id)->toBe($case->id);
});

test('ModerationAction has one appeal', function (): void {
    $action = ModerationAction::factory()->create();
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    expect($action->appeal->id)->toBe($appeal->id);
});

test('ModerationAppeal belongs to action', function (): void {
    $action = ModerationAction::factory()->create();
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    expect($appeal->action->id)->toBe($action->id);
});

test('ModerationReport belongs to case', function (): void {
    $case = ModerationCase::factory()->create();
    $report = ModerationReport::factory()->create(['case_id' => $case->id]);

    expect($report->case->id)->toBe($case->id);
});

test('ModerationCase casts enums correctly', function (): void {
    $case = ModerationCase::factory()->create([
        'source_platform' => 'discord',
        'source' => 'user_report',
        'status' => 'pending',
        'severity' => 'high',
        'violation_type' => 'spam',
    ]);

    $case->refresh();

    expect($case->source_platform)->toBe(Platform::Discord)
        ->and($case->source)->toBe(CaseSource::UserReport)
        ->and($case->status)->toBe(CaseStatus::Pending)
        ->and($case->severity)->toBe(Severity::High)
        ->and($case->violation_type)->toBe(ViolationType::Spam);
});
