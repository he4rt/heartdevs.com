<?php

declare(strict_types=1);

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create(['name' => 'Admin']);
    actingAs($this->admin);

    Activity::query()->delete();
});

it('lists activities with subject, causer and risk', function (): void {
    $team = Team::factory()->create(['name' => 'He4rt']);

    get(route('panel-admin::activity.index', ['filter' => ['subject_type' => $team->getMorphClass()]]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('activity/index')
            ->where('activities.data.0.event', 'created')
            ->where('activities.data.0.subjectType', 'Team')
            ->where('activities.data.0.subjectLabel', 'He4rt')
            ->where('activities.data.0.causerName', 'Admin')
            ->whereType('activities.data.0.riskLevel', 'string')
            ->has('filters.events'));
});

it('filters by event and risk level', function (): void {
    $team = Team::factory()->create();
    $team->delete();

    $deleted = Activity::query()->where('event', 'deleted')->sole();

    get(route('panel-admin::activity.index', ['filter' => ['event' => 'deleted', 'risk_level' => $deleted->getAttribute('risk_level')]]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.id', $deleted->id));
});

it('shows the changes with sensitive values hidden, the request and the integrity', function (): void {
    $team = Team::factory()->create(['name' => 'He4rt']);
    AuditActivity::changed($team, 'updated', attributes: ['name' => 'New', 'secret' => 'x'], old: ['name' => 'Old', 'secret' => 'y'], log: Team::class);
    $teamCreated = Activity::query()->where('event', 'created')->where('subject_id', $team->id)->sole();
    $activity = Activity::query()->where('event', 'updated')->sole();

    get(route('panel-admin::activity.show', $activity))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('activity/show')
            ->where('activity.changes.0.field', 'name')
            ->where('activity.changes.0.old', 'Old')
            ->where('activity.changes.0.new', 'New')
            ->where('activity.changes.1.new', '[REDACTED]')
            ->where('activity.integrity', 'valid')
            ->has('activity.context.request_id')
            ->where('activity.related', fn ($related): bool => collect($related)->pluck('id')->contains($teamCreated->id)));
});

it('flags a tampered row', function (): void {
    $activity = Activity::query()->create(['description' => 'original', 'log_name' => 'default']);
    DB::table('activity_log')->where('id', $activity->id)->update(['description' => 'forged']);

    get(route('panel-admin::activity.show', $activity))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('activity.integrity', 'tampered'));
});
