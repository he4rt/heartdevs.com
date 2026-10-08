<?php

declare(strict_types=1);

use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\Team\Queries\DelasCandidates;
use He4rt\Identity\User\Models\User;

beforeEach(function (): void {
    $this->candidates = resolve(DelasCandidates::class);
    $this->lead = User::factory()->delasLead()->create();
});

/**
 * Ids que a busca do seletor de conceder devolve para a líder.
 *
 * @return list<string>
 */
function delasGrantSearchIds(DelasCandidates $candidates, User $lead, string $term): array
{
    return $candidates->search($candidates->forGrant($lead), $term)->pluck('id')->all();
}

// Escopo de cada seletor

test('conceder: aparece quem não tem a tag, inclusive com pedido pendente ou rejeitado', function (): void {
    $withoutRequest = User::factory()->create();
    $pending = DelasTagRequest::factory()->pending()->create()->user;
    $rejected = DelasTagRequest::factory()->rejected()->create()->user;
    $member = DelasTagRequest::factory()->approved()->create()->user;

    $ids = $this->candidates->forGrant($this->lead)->pluck('id')->all();

    expect($ids)->toContain($withoutRequest->id, $pending->id, $rejected->id)->not->toContain($member->id);
});

test('remover: só aparece quem tem a tag', function (): void {
    $member = DelasTagRequest::factory()->approved()->create()->user;
    DelasTagRequest::factory()->revoked()->create();
    DelasTagRequest::factory()->pending()->create();
    User::factory()->create();

    $ids = $this->candidates->forRevoke($this->lead)->pluck('id')->all();

    expect($ids)->toBe([$member->id]);
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

    expect($ids)->toContain($member->id, $streamer->id)->not->toContain($withoutTag->id, $superAdmin->id, $moderator->id, $otherLead->id);
});

test('quem age nunca aparece no próprio seletor', function (string $scope, string $actorKind): void {
    // A líder tem a tag, então só não aparece em remover/adicionar por ser quem age.
    DelasTagRequest::factory()->for($this->lead)->approved()->create();
    $actor = $actorKind === 'superAdmin' ? User::factory()->superAdmin()->create() : $this->lead;

    $people = $this->candidates->{$scope}($actor)->pluck('id');

    expect($people)->not->toContain($actor->id);
})->with([
    'conceder, como super admin' => ['forGrant', 'superAdmin'],
    'conceder, como líder' => ['forGrant', 'lead'],
    'remover, como líder' => ['forRevoke', 'lead'],
    'adicionar moderadora, como líder' => ['forModerator', 'lead'],
]);

// Busca

describe('busca por nome ou @username, dentro do escopo', function (): void {
    beforeEach(function (): void {
        $this->ana = User::factory()->create(['name' => 'Ana Souza', 'username' => 'anasouza']);
        $this->bia = User::factory()->create(['name' => 'Beatriz Lima', 'username' => 'bialima']);
        $member = User::factory()->create(['name' => 'Ana Membra', 'username' => 'anamembra']);
        // Decisora fixa: um nome aleatório da factory (ex.: "Mariana") também casaria com "ana".
        DelasTagRequest::factory()->for($member)->approved()->create(['decided_by' => $this->lead->getKey()]);
    });

    test('acha pelo nome, sem quem já tem a tag', function (): void {
        $ids = delasGrantSearchIds($this->candidates, $this->lead, 'ana');

        expect($ids)->toBe([$this->ana->id]);
    });

    test('acha pelo @username', function (): void {
        $ids = delasGrantSearchIds($this->candidates, $this->lead, '@bialima');

        expect($ids)->toBe([$this->bia->id]);
    });
});

test('a busca respeita o limite', function (): void {
    User::factory()->count(3)->create(['name' => 'Carla']);

    $results = $this->candidates->search($this->candidates->forGrant($this->lead), 'carla', limit: 2);

    expect($results)->toHaveCount(2);
});

test('a busca ignora acento e maiúscula', function (string $term): void {
    $thais = User::factory()->create(['name' => 'Thaís Barbosa', 'username' => 'thaisb']);

    $ids = delasGrantSearchIds($this->candidates, $this->lead, $term);

    expect($ids)->toContain($thais->id);
})->with(['thais', 'THAÍS']);
