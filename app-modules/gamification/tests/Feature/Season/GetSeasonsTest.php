<?php

declare(strict_types=1);

use He4rt\Gamification\Season\Models\Season;

test('get seasons success', function (): void {
    Season::factory()->create();

    $response = $this->actingAsAdmin()->get(route('get-seasons'));

    $response->assertOk();
    $response->assertJsonStructure([
        [
            'id',
            'name',
            'description',
            'messages_count',
            'participants_count',
            'meeting_count',
            'badges_count',
            'started_at',
            'ended_at',
        ],
    ]);
});
