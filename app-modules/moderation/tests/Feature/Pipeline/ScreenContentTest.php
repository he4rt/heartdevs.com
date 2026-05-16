<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\RouteCaseAction;
use He4rt\Moderation\Classification\Jobs\ScreenContent;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('AI flags content above threshold and creates case', function (): void {
    Event::fake([CaseCreated::class, CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => true,
                'categories' => [],
                'category_scores' => [
                    'harassment' => 0.85,
                    'hate' => 0.1,
                ],
            ]],
        ]),
    ]);

    $user = User::factory()->create();
    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-ext-456',
    ]);

    $dto = new ModerationContentDTO(
        contentId: 'msg-ai-flag',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'discord-ext-456',
        textContent: 'extremely toxic content here',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'extremely toxic content here'],
        tenantId: null,
    );

    $job = new ScreenContent($dto, CaseSource::AutoDetect);
    $job->handle(resolve(RouteCaseAction::class));

    expect(ModerationCase::query()->count())->toBe(1);

    $case = ModerationCase::query()->first();
    expect($case->content_id)->toBe('msg-ai-flag')
        ->and($case->ai_scores)->toHaveKey('harassment')
        ->and($case->author_id)->toBe($user->id);

    Event::assertDispatched(CaseCreated::class);
    Event::assertDispatched(CaseQueued::class);
});

test('AI clears content below threshold and no case is created', function (): void {
    Event::fake([CaseCreated::class, CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => false,
                'categories' => [],
                'category_scores' => [
                    'harassment' => 0.1,
                    'hate' => 0.05,
                ],
            ]],
        ]),
    ]);

    $dto = new ModerationContentDTO(
        contentId: 'msg-ai-clear',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '',
        textContent: 'this is a normal message',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'this is a normal message'],
        tenantId: null,
    );

    $job = new ScreenContent($dto, CaseSource::AutoDetect);
    $job->handle(resolve(RouteCaseAction::class));

    expect(ModerationCase::query()->count())->toBe(0);

    Event::assertNotDispatched(CaseCreated::class);
    Event::assertNotDispatched(CaseQueued::class);
});

test('CaseReadyForEnforcement is NOT emitted since classifier is aggregate/openai', function (): void {
    Event::fake([CaseCreated::class, CaseQueued::class, CaseReadyForEnforcement::class]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => true,
                'categories' => [],
                'category_scores' => [
                    'harassment' => 0.9,
                ],
            ]],
        ]),
    ]);

    $dto = new ModerationContentDTO(
        contentId: 'msg-no-enforce',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '',
        textContent: 'very harassing content',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'very harassing content'],
        tenantId: null,
    );

    $job = new ScreenContent($dto, CaseSource::AutoDetect);
    $job->handle(resolve(RouteCaseAction::class));

    // classifier_version will be 'aggregate' or 'openai', never 'rules'
    Event::assertNotDispatched(CaseReadyForEnforcement::class);
});
