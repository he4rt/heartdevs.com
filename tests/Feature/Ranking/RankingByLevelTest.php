<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Heart\Character\Infrastructure\Models\Character;
use Symfony\Component\HttpFoundation\Response;

uses(DatabaseTransactions::class);

test('can fetch ranking', function (): void {
    Character::factory()->count(5)->create();

    $response = $this
        ->actingAsAdmin()
        ->getJson(route('ranking.leveling'));

    $response->assertStatus(Response::HTTP_OK);
});
