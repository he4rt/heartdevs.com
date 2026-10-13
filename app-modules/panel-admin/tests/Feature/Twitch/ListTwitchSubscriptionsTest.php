<?php

declare(strict_types=1);

use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\PanelAdmin\Twitch\Resources\TwitchSubscriptionResource\Pages\ListTwitchSubscriptions;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function Pest\Livewire\livewire;

function bindAdminTwitchHelix(MockClient $mock): void
{
    Cache::put('twitch_app_access_token', 'fake-token', 3_600);

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(
            tokenService: new TwitchAppTokenService(
                new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret'),
            ),
            clientId: 'fake-client-id',
        ),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    ));
}

function createLocalTwitchSubscription(): TwitchSubscription
{
    return TwitchSubscription::query()->create([
        'subscription_id' => 'sub-local',
        'type' => 'stream.online',
        'status' => TwitchSubscriptionStatus::Enabled,
        'broadcaster_user_id' => '12345',
        'condition' => ['broadcaster_user_id' => '12345'],
        'transport' => 'webhook',
        'callback_url' => 'https://example.com/api/webhooks/twitch/eventsub',
        'cost' => 0,
        'version' => '1',
    ]);
}

beforeEach(function (): void {
    $this->actingAs(User::factory()->superAdmin()->create());

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('o sync que falha na Twitch preserva as subscriptions locais', function (): void {
    createLocalTwitchSubscription();

    bindAdminTwitchHelix(new MockClient([
        ListSubscriptions::class => MockResponse::make(['message' => 'Invalid OAuth token'], 401),
    ]));

    livewire(ListTwitchSubscriptions::class)
        ->callAction('sync')
        ->assertNotified(__('panel-admin::twitch.subscriptions.actions.sync_failed'));

    expect(TwitchSubscription::query()->count())->toBe(1);
});

test('o delete que falha na Twitch mantém a subscription local', function (): void {
    $subscription = createLocalTwitchSubscription();

    bindAdminTwitchHelix(new MockClient([
        DeleteSubscription::class => MockResponse::make(['message' => 'Internal Server Error'], 500),
    ]));

    livewire(ListTwitchSubscriptions::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($subscription))
        ->assertNotified(__('panel-admin::twitch.subscriptions.actions.delete_failed'));

    expect(TwitchSubscription::query()->whereKey($subscription->getKey())->exists())->toBeTrue();
});

test('o delete remove a linha local quando a subscription já não existe na Twitch', function (): void {
    $subscription = createLocalTwitchSubscription();

    bindAdminTwitchHelix(new MockClient([
        DeleteSubscription::class => MockResponse::make(['message' => 'Not Found'], 404),
    ]));

    livewire(ListTwitchSubscriptions::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($subscription));

    expect(TwitchSubscription::query()->whereKey($subscription->getKey())->exists())->toBeFalse();
});
