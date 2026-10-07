<?php

declare(strict_types=1);

use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\Identity\User\Models\User;

beforeEach(function (): void {
    $this->candidates = resolve(DelasCandidates::class);
    $this->lead = User::factory()->delasLead()->create();
});

test('conceder: aparece quem não tem a tag, inclusive com pedido pendente ou rejeitado', function (): void {
    $withoutRequest = User::factory()->create();
    $pending = DelasTagRequest::factory()->pending()->create()->user;
    $rejected = DelasTagRequest::factory()->rejected()->create()->user;
    $member = DelasTagRequest::factory()->approved()->create()->user;

    $ids = $this->candidates->forGrant($this->lead)->pluck('id')->all();

    expect($ids)->toContain($withoutRequest->id, $pending->id, $rejected->id)
        ->not->toContain($member->id);
});

test('remover: só aparece quem tem a tag', function (): void {
    $member = DelasTagRequest::factory()->approved()->create()->user;
    DelasTagRequest::factory()->revoked()->create();
    DelasTagRequest::factory()->pending()->create();
    User::factory()->create();

    expect($this->candidates->forRevoke($this->lead)->pluck('id')->all())->toBe([$member->id]);
});

test('adicionar moderadora: só quem tem a tag, fora super admin, moderadora ou líder', function (): void {
    $member = User::factory()->create();
    $streamer = User::factory()->streamer()->create();
    $withoutTag = User::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $moderator = User::factory()->delasModerator()->create();
    $otherLead = User::factory()->delasLead()->create();

    foreach ([$member, $streamer, $superAdmin, $moderator, $otherLead] as $user) {
        DelasTagRequest::factory()->for($user)->approved()->create(['decided_by' => $this->lead->getKey()]);
    }

    $ids = $this->candidates->forModerator($this->lead)->pluck('id')->all();

    expect($ids)->toContain($member->id, $streamer->id)
        ->not->toContain($withoutTag->id, $superAdmin->id, $moderator->id, $otherLead->id);
});

test('a própria pessoa que age nunca aparece', function (): void {
    DelasTagRequest::factory()->for($this->lead)->approved()->create();
    $actor = User::factory()->superAdmin()->create();

    expect($this->candidates->forGrant($actor)->pluck('id'))->not->toContain($actor->id)
        ->and($this->candidates->forRevoke($this->lead)->pluck('id'))->not->toContain($this->lead->id)
        ->and($this->candidates->forModerator($this->lead)->pluck('id'))->not->toContain($this->lead->id)
        ->and($this->candidates->forGrant($this->lead)->pluck('id'))->not->toContain($this->lead->id);
});

test('a busca acha por nome ou @username, dentro do escopo', function (): void {
    $ana = User::factory()->create(['name' => 'Ana Souza', 'username' => 'anasouza']);
    $bia = User::factory()->create(['name' => 'Beatriz Lima', 'username' => 'bialima']);
    $member = User::factory()->create(['name' => 'Ana Membra', 'username' => 'anamembra']);
    // Decisora fixa: um nome aleatório da factory (ex.: "Mariana") também casaria com "ana".
    DelasTagRequest::factory()->for($member)->approved()->create(['decided_by' => $this->lead->getKey()]);

    $byName = $this->candidates->search($this->candidates->forGrant($this->lead), 'ana')->pluck('id')->all();
    $byUsername = $this->candidates->search($this->candidates->forGrant($this->lead), '@bialima')->pluck('id')->all();

    expect($byName)->toBe([$ana->id])
        ->and($byUsername)->toBe([$bia->id]);
});

test('a busca respeita o limite', function (): void {
    User::factory()->count(3)->create(['name' => 'Carla']);

    expect($this->candidates->search($this->candidates->forGrant($this->lead), 'carla', limit: 2))->toHaveCount(2);
});
