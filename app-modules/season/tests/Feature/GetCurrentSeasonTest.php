<?php

declare(strict_types=1);

use He4rt\Season\Models\Season;
use Illuminate\Support\Facades\Config;

test('get current season success', function (): void {
    $season = Season::factory()->create();

    Config::set('he4rt.season.id', $season->id);

    $response = $this->actingAsAdmin()->get(route('seasons.current'));

    $response->assertOk();
    $response->assertJsonStructure([
        'id',
        'name',
        'description',
        'messagesCount',
        'participantsCount',
        'meetingCount',
        'badgesCount',
        'startAt',
        'endAt',
    ]);
});
