<?php

declare(strict_types=1);

use App\Enums\Tone;
use He4rt\Identity\Teams\TeamStatus;

it('gives each team status a tone', function (TeamStatus $status, Tone $tone): void {
    expect($status->getTone())->toBe($tone);
})->with([
    'active' => [TeamStatus::Active, Tone::Success],
    'suspended' => [TeamStatus::Suspended, Tone::Warning],
    'archived' => [TeamStatus::Archived, Tone::Danger],
]);

it('labels a team status with its translation', function (): void {
    expect(TeamStatus::Suspended->getLabel())->toBe(__('teams::team_status.suspended.label'))
        ->not->toBe('teams::team_status.suspended.label');
});
