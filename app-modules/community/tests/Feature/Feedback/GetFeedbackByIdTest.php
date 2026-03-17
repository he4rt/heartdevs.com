<?php

declare(strict_types=1);

use He4rt\Community\Feedback\Models\Feedback;
use Symfony\Component\HttpFoundation\Response;

test('can find by id', function (): void {
    $feedback = Feedback::factory()->create();

    $this
        ->actingAsAdmin()
        ->getJson(route('feedbacks.show', ['feedbackId' => $feedback->id]))
        ->assertStatus(Response::HTTP_OK);
});
