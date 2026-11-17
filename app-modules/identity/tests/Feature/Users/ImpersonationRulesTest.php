<?php

declare(strict_types=1);

use He4rt\Identity\Users\Actions\StartImpersonation;
use He4rt\Identity\Users\Exceptions\CannotImpersonate;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;

use function Pest\Laravel\actingAs;

it('explains each refusal', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $otherAdmin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $impersonation = resolve(Impersonation::class);

    expect($impersonation->reasonToRefuse($admin, $user))->toBeNull()
        ->and($impersonation->reasonToRefuse($admin, $admin))->toBe('You cannot impersonate yourself.')
        ->and($impersonation->reasonToRefuse($admin, $otherAdmin))->toBe('You cannot impersonate another super admin.')
        ->and($impersonation->reasonToRefuse($user, User::factory()->create()))->toBe('Only a super admin can impersonate.');
});

it('does not nest impersonations', function (): void {
    $admin = User::factory()->superAdmin()->create();
    actingAs($admin);
    $impersonation = resolve(Impersonation::class);
    $startImpersonation = resolve(StartImpersonation::class);

    $startImpersonation->handle($admin, User::factory()->create(), returnTo: '/control');

    expect($impersonation->isActive())->toBeTrue()
        ->and($impersonation->impersonator()?->is($admin))->toBeTrue()
        ->and(fn () => $startImpersonation->handle($admin, User::factory()->create(), returnTo: '/control'))
        ->toThrow(CannotImpersonate::class, 'You are already impersonating a user.');
});
