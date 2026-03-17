<?php

declare(strict_types=1);

use He4rt\Gamification\Badge\Models\Badge;
use Symfony\Component\HttpFoundation\Response;

test('can delete badge', function (): void {
    $badge = Badge::factory()->create();

    $this->actingAsAdmin()
        ->deleteJson(route('badges.destroy', ['badgeId' => $badge->id]))
        ->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('badges', [
        'id' => $badge->id,
    ]);
});
