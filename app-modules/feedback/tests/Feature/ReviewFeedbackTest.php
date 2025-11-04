<?php

declare(strict_types=1);

use He4rt\Feedback\Models\Feedback;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

dataset('data provider', fn () => [
    'approve feedback' => [
        'action' => 'approved',
        'payload' => [],
        'expected' => [
            'status' => 'approved',
        ],
    ],
    'decline feedback' => [
        'action' => 'declined',
        'payload' => [
            'reason' => 'bobo',
        ],
        'expected' => [
            'status' => 'declined',
            'reason' => 'bobo',
        ],
    ],
]);

test('can handle feedback', function (string $action, array $payload, array $expected): void {

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            Provider::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'provider_id' => '123',
            ])->create();
        })
        ->create();

    $feedback = Feedback::factory()->create(['tenant_id' => $tenant->getKey()]);
    $staffProvider = Provider::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);

    $payload['staff_id'] = $staffProvider->provider_id;
    $response = $this
        ->actingAsAdmin()
        ->postJson(route('feedbacks.review', [
            'feedbackId' => $feedback->id,
            'action' => $action,
        ]), $payload);

    $response->assertStatus(Response::HTTP_CREATED);

    $expected['staff_id'] = $staffProvider->model_id;
    $this->assertDatabaseHas('feedback_reviews', $expected);
})->with('data provider');
