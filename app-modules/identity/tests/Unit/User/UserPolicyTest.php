<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Identity\User\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->policy = new UserPolicy();
});

test('viewAny allows staff, compliance, recruiter and squad captain', function (): void {
    expect($this->policy->viewAny(User::factory()->staff()->create()))->toBeTrue()
        ->and($this->policy->viewAny(User::factory()->compliance()->create()))->toBeTrue()
        ->and($this->policy->viewAny(User::factory()->recruiter()->create()))->toBeTrue()
        ->and($this->policy->viewAny(User::factory()->squadCaptain()->create()))->toBeTrue();
});

test('viewAny denies member', function (): void {
    expect($this->policy->viewAny(User::factory()->create()))->toBeFalse();
});

test('view allows staff, compliance, recruiter and squad captain', function (): void {
    expect($this->policy->view(User::factory()->staff()->create()))->toBeTrue()
        ->and($this->policy->view(User::factory()->compliance()->create()))->toBeTrue()
        ->and($this->policy->view(User::factory()->recruiter()->create()))->toBeTrue()
        ->and($this->policy->view(User::factory()->squadCaptain()->create()))->toBeTrue();
});

test('view denies member', function (): void {
    expect($this->policy->view(User::factory()->create()))->toBeFalse();
});

test('update and delete allow staff and compliance', function (): void {
    $staff = User::factory()->staff()->create();
    $compliance = User::factory()->compliance()->create();

    expect($this->policy->update($staff))->toBeTrue()
        ->and($this->policy->delete($staff))->toBeTrue()
        ->and($this->policy->update($compliance))->toBeTrue()
        ->and($this->policy->delete($compliance))->toBeTrue();
});

test('update and delete deny non-staff roles', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();

    expect($this->policy->update($user))->toBeFalse()
        ->and($this->policy->delete($user))->toBeFalse();
})->with([
    'member' => 'member',
    'recruiter' => 'recruiter',
    'squad captain' => 'squadCaptain',
]);

test('restore and forceDelete require compliance', function (): void {
    $compliance = User::factory()->compliance()->create();
    $staff = User::factory()->staff()->create();

    expect($this->policy->restore($compliance))->toBeTrue()
        ->and($this->policy->forceDelete($compliance))->toBeTrue()
        ->and($this->policy->restore($staff))->toBeFalse()
        ->and($this->policy->forceDelete($staff))->toBeFalse();
});
