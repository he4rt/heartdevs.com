<?php

declare(strict_types=1);

use He4rt\Community\Feedback\Models\Feedback;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
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
            ExternalIdentity::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'external_account_id' => '123',
            ])->create();
        })
        ->create();

    $feedback = Feedback::factory()->create(['tenant_id' => $tenant->getKey()]);
    $staffProvider = ExternalIdentity::factory()->create(['tenant_id' => $tenant->getKey(), 'provider' => 'discord']);

    $payload['staff_id'] = $staffProvider->external_account_id;
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
