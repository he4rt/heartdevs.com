<?php

declare(strict_types=1);

use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('o banco recusa duas solicitações ativas da mesma pessoa', function (string $first, string $second): void {
    $user = User::factory()->create();

    DelasTagRequest::factory()->for($user)->{$first}()->create();

    expect(fn () => DelasTagRequest::factory()->for($user)->{$second}()->create())
        ->toThrow(UniqueConstraintViolationException::class);
})->with([
    'pendente + pendente' => ['pending', 'pending'],
    'aprovada + pendente' => ['approved', 'pending'],
]);

test('solicitações encerradas não ocupam o lugar da pessoa', function (): void {
    $user = User::factory()->create();

    DelasTagRequest::factory()->for($user)->rejected()->create();
    DelasTagRequest::factory()->for($user)->revoked()->create();
    $pending = DelasTagRequest::factory()->for($user)->pending()->create();

    expect(DelasTagRequest::query()->where('user_id', $user->getKey())->active()->sole()->is($pending))->toBeTrue();
});

test('o banco recusa dois bloqueios ativos da mesma pessoa, mas guarda os encerrados', function (): void {
    $user = User::factory()->create();

    DelasRequesterBlock::factory()->for($user)->lifted()->create();
    DelasRequesterBlock::factory()->for($user)->create();

    expect(DelasRequesterBlock::query()->where('user_id', $user->getKey())->count())->toBe(2)
        ->and(fn () => DelasRequesterBlock::factory()->for($user)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

test('o histórico guarda enums e só tem data de criação', function (): void {
    $transition = DelasTransition::factory()->create([
        'action' => DelasAction::Approved,
        'from_status' => DelasRequestStatus::Pending,
        'to_status' => DelasRequestStatus::Approved,
    ])->fresh();

    expect($transition->action)->toBe(DelasAction::Approved)
        ->and($transition->from_status)->toBe(DelasRequestStatus::Pending)
        ->and($transition->to_status)->toBe(DelasRequestStatus::Approved)
        ->and($transition->created_at)->not->toBeNull()
        ->and($transition->getAttributes())->not->toHaveKey('updated_at');
});
