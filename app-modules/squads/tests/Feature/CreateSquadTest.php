<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Actions\CreateSquad;
use He4rt\Squads\Enums\SquadStatus;
use He4rt\Squads\Models\Squad;
use Illuminate\Auth\Access\AuthorizationException;

test('super-admin creates a squad as draft', function (): void {
    config(['he4rt.admins' => 'guisaliba']);

    $actor = User::factory()->create([
        'username' => 'guisaliba',
    ]);

    $squad = resolve(CreateSquad::class)->handle(
        actor: $actor,
        name: 'Test Squad',
        objective: 'This is a test squad.',
    );

    expect($squad)->toBeInstanceOf(Squad::class)
        ->and($squad->name)->toBe('Test Squad')
        ->and($squad->slug)->toBe('test-squad')
        ->and($squad->objective)->toBe('This is a test squad.')
        ->and($squad->status)->toBe(SquadStatus::Draft);

    $this->assertDatabaseHas('squads', [
        'name' => 'Test Squad',
        'slug' => 'test-squad',
        'objective' => 'This is a test squad.',
        'status' => 'draft',
    ]);
});

test('duplicate squad names receive unique slugs', function (): void {
    config(['he4rt.admins' => 'guisaliba']);

    $actor = User::factory()->create([
        'username' => 'guisaliba',
    ]);

    $firstSquad = resolve(CreateSquad::class)->handle(
        actor: $actor,
        name: 'Test Squad',
    );
    $secondSquad = resolve(CreateSquad::class)->handle(
        actor: $actor,
        name: 'Test Squad',
    );

    expect($firstSquad->slug)->toBe('test-squad')
        ->and($secondSquad->slug)->toBe('test-squad-2');
});

test('common user cannot create a squad', function (): void {
    config(['he4rt.admins' => 'guisaliba']);

    $actor = User::factory()->create([
        'username' => 'common-user',
    ]);

    resolve(CreateSquad::class)->handle(
        actor: $actor,
        name: 'Test Squad',
    );
})->throws(AuthorizationException::class);
