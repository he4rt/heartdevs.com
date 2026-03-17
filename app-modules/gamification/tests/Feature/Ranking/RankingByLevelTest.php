<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Models\Character;
use Symfony\Component\HttpFoundation\Response;

test('can fetch ranking', function (): void {
    Character::factory()->count(5)->create();

    $response = $this
        ->actingAsAdmin()
        ->getJson(route('ranking.leveling'));

    $response->assertStatus(Response::HTTP_OK);
});
