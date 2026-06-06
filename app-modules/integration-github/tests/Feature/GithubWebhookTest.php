<?php

declare(strict_types=1);

use He4rt\IntegrationGithub\Enums\ContributionType;
use He4rt\IntegrationGithub\Events\GithubContributionRecorded;
use He4rt\IntegrationGithub\Models\GithubContribution;
use He4rt\IntegrationGithub\Models\GithubEventLog;
use He4rt\IntegrationGithub\Models\GithubRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config(['services.github.webhook_secret' => 'test-secret']);
});

/**
 * @param  array<string, mixed>  $payload
 */
function postGithubWebhook(string $event, array $payload, ?string $delivery = null, string $secret = 'test-secret'): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    return test()->postJson('/api/webhooks/github', $payload, [
        'X-GitHub-Event' => $event,
        'X-GitHub-Delivery' => $delivery ?? Str::uuid()->toString(),
        'X-Hub-Signature-256' => $signature,
    ]);
}

/**
 * @return array<string, mixed>
 */
function prWebhookPayload(string $repo = 'he4rt/heartdevs.com', int $number = 1, string $login = 'maria', int $id = 42): array
{
    return [
        'action' => 'opened',
        'repository' => ['full_name' => $repo],
        'sender' => ['login' => $login, 'id' => $id],
        'pull_request' => [
            'number' => $number, 'title' => 'feat: x', 'state' => 'open', 'merged_at' => null,
            'created_at' => '2026-06-01T12:00:00Z', 'html_url' => 'u',
            'additions' => 5, 'deletions' => 1, 'changed_files' => 2,
            'user' => ['login' => $login, 'id' => $id],
        ],
    ];
}

it('rejeita assinatura inválida com 403 e não grava nada', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);

    postGithubWebhook('pull_request', prWebhookPayload(), secret: 'wrong-secret')
        ->assertForbidden();

    expect(GithubEventLog::query()->count())->toBe(0)
        ->and(GithubContribution::query()->count())->toBe(0);
});

it('grava no lake e projeta a contribuição para repo na allowlist, emitindo o evento', function (): void {
    Event::fake([GithubContributionRecorded::class]);
    $repo = GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);

    postGithubWebhook('pull_request', prWebhookPayload())->assertSuccessful();

    expect(GithubEventLog::query()->count())->toBe(1);

    $contribution = GithubContribution::query()->where('external_ref', 'pr:1')->sole();

    expect($contribution->type)->toBe(ContributionType::Pr)
        ->and($contribution->tenant_id)->toBe($repo->tenant_id)
        ->and($contribution->actor_login)->toBe('maria')
        ->and($contribution->metadata['additions'])->toBe(5);

    Event::assertDispatched(GithubContributionRecorded::class);
});

it('faz fan-out: projeta uma contribuição por tenant que acompanha o repo', function (): void {
    $a = GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);
    $b = GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);
    GithubRepository::factory()->disabled()->create(['full_name' => 'he4rt/heartdevs.com']);

    postGithubWebhook('pull_request', prWebhookPayload())->assertSuccessful();

    expect(GithubEventLog::query()->count())->toBe(1)
        ->and(GithubContribution::query()->where('external_ref', 'pr:1')->pluck('tenant_id')->sort()->values()->all())
        ->toBe(collect([$a->tenant_id, $b->tenant_id])->sort()->values()->all());
});

it('deduplica entregas repetidas pelo delivery id', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);
    $delivery = Str::uuid()->toString();

    postGithubWebhook('pull_request', prWebhookPayload(), delivery: $delivery)->assertSuccessful();
    postGithubWebhook('pull_request', prWebhookPayload(), delivery: $delivery)->assertSuccessful();

    expect(GithubEventLog::query()->count())->toBe(1)
        ->and(GithubContribution::query()->where('external_ref', 'pr:1')->count())->toBe(1);
});

it('grava no lake mas NÃO projeta para repo fora da allowlist', function (): void {
    postGithubWebhook('pull_request', prWebhookPayload('he4rt/secret-repo'))->assertSuccessful();

    expect(GithubEventLog::query()->count())->toBe(1)
        ->and(GithubContribution::query()->count())->toBe(0);
});

it('projeta issues e pushes (commits)', function (): void {
    GithubRepository::factory()->create(['full_name' => 'he4rt/heartdevs.com']);

    postGithubWebhook('issues', [
        'action' => 'opened',
        'repository' => ['full_name' => 'he4rt/heartdevs.com'],
        'sender' => ['login' => 'ana', 'id' => 3],
        'issue' => ['number' => 10, 'title' => 'bug', 'state' => 'open', 'created_at' => '2026-06-01T00:00:00Z', 'html_url' => 'u', 'user' => ['login' => 'ana', 'id' => 3]],
    ])->assertSuccessful();

    postGithubWebhook('push', [
        'repository' => ['full_name' => 'he4rt/heartdevs.com'],
        'sender' => ['login' => 'maria', 'id' => 42],
        'commits' => [
            ['id' => 'sha1', 'url' => 'u', 'timestamp' => '2026-06-04T08:00:00Z', 'author' => ['username' => 'maria', 'name' => 'Maria']],
        ],
    ])->assertSuccessful();

    expect(GithubContribution::query()->where('external_ref', 'issue:10')->exists())->toBeTrue()
        ->and(GithubContribution::query()->where('external_ref', 'commit:sha1')->exists())->toBeTrue();
});
