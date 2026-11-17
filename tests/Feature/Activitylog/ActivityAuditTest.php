<?php

declare(strict_types=1);

use App\Support\Activitylog\ActivityIntegrity;
use App\Support\Activitylog\ActivityRedactor;
use App\Support\Activitylog\ActivityRisk;
use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Teams\Team;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

function teamActivity(Team $team, string $event): Activity
{
    return Activity::query()->where('subject_id', $team->getKey())->where('event', $event)->latest('id')->firstOrFail();
}

it('scores and signs each new activity', function (): void {
    $team = Team::factory()->create();
    $team->delete();

    $activity = teamActivity($team, 'deleted');

    expect($activity->getAttribute('risk_score'))->toBeGreaterThanOrEqual(45)
        ->and($activity->getAttribute('risk_level'))->toBe(ActivityRisk::level((int) $activity->getAttribute('risk_score')))
        ->and(resolve(ActivityIntegrity::class)->verify($activity))->toBeTrue();
});

it('weights the event, the log name, sensitive fields and the request ip', function (): void {
    $team = Team::factory()->create();
    AuditActivity::changed($team, 'updated', attributes: ['email' => 'a@b.c'], log: 'security');

    expect(ActivityRisk::score(teamActivity($team, 'updated')))->toBe(10 + 35 + 15 + 5)
        ->and(ActivityRisk::level(10))->toBe('low')
        ->and(ActivityRisk::level(25))->toBe('medium')
        ->and(ActivityRisk::level(55))->toBe('high')
        ->and(ActivityRisk::level(100))->toBe('critical');
});

it('detects a row changed after it was signed', function (): void {
    $team = Team::factory()->create();
    $activity = teamActivity($team, 'created');

    DB::table('activity_log')->where('id', $activity->getKey())->update(['description' => 'forged']);

    expect(resolve(ActivityIntegrity::class)->verify($activity->refresh()))->toBeFalse();
});

it('hides sensitive values, also when nested', function (): void {
    expect(ActivityRedactor::redact(['name' => 'Ada', 'password' => 'secret', 'meta' => ['api_token' => 't', 'color' => 'red']]))
        ->toBe(['name' => 'Ada', 'password' => '[REDACTED]', 'meta' => ['api_token' => '[REDACTED]', 'color' => 'red']]);
});

it('caps the score at 100', function (): void {
    $team = Team::factory()->create();
    AuditActivity::changed($team, 'force_deleted', attributes: ['password' => 'x', 'api_key' => 'y'], log: 'permissions');

    expect(ActivityRisk::score(teamActivity($team, 'force_deleted')))->toBe(100);
});
