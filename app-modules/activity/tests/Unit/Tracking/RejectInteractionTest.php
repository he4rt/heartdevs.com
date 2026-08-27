<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Actions\RejectInteraction;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Models\Interaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('rejects interaction', function (): void {
    $interaction = Interaction::factory()->create([
        'status' => ActivityStatus::Pending,
    ]);

    $result = resolve(RejectInteraction::class)->handle($interaction);

    expect($result->status)->toBe(ActivityStatus::Rejected)
        ->and($result->reviewed_at)->not->toBeNull();
});
