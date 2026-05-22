<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Console\Command;
use Saloon\Exceptions\Request\RequestException;

final class SubscribeTwitchEventsCommand extends Command
{
    protected $signature = 'twitch:subscribe
        {broadcaster_user_id : The Twitch broadcaster user ID}
        {--type= : Subscribe to a specific event type}
        {--all : Subscribe to all available event types}
        {--clear-all : Delete all existing subscriptions for this broadcaster}';

    protected $description = 'Manage Twitch EventSub webhook subscriptions for a broadcaster';

    public function handle(TwitchHelixConnector $helix): int
    {
        $broadcasterId = $this->argument('broadcaster_user_id');

        $this->showConfig($broadcasterId);

        if ($this->option('clear-all')) {
            return $this->clearAllSubscriptions($helix, $broadcasterId);
        }

        $specificType = $this->option('type');

        if (!$specificType && !$this->option('all')) {
            $this->error('Specify --type=<event_type>, --all, or --clear-all.');

            return self::FAILURE;
        }

        return $this->createSubscriptions($helix, $broadcasterId, $specificType);
    }

    private function showConfig(string $broadcasterId): void
    {
        $callbackUrl = config()->string('services.twitch.eventsub_callback');
        $secret = config()->string('services.twitch.eventsub_secret');
        $maskedSecret = mb_substr($secret, 0, 4).str_repeat('*', max(mb_strlen($secret) - 4, 0));

        $this->table(['Setting', 'Value'], [
            ['Broadcaster ID', $broadcasterId],
            ['Callback URL', $callbackUrl],
            ['Secret', $maskedSecret],
        ]);
    }

    private function createSubscriptions(TwitchHelixConnector $helix, string $broadcasterId, ?string $specificType): int
    {
        $existingTypes = $this->getExistingSubscriptionTypes($helix, $broadcasterId);

        $types = $specificType
            ? [TwitchEventSubType::from($specificType)]
            : TwitchEventSubType::cases();

        $callbackUrl = config()->string('services.twitch.eventsub_callback');
        $secret = config()->string('services.twitch.eventsub_secret');

        $results = [];

        foreach ($types as $type) {
            if (in_array($type->value, $existingTypes, true)) {
                $results[] = [$type->value, $type->getVersion(), 'already_exists'];

                continue;
            }

            try {
                $helix->send(new CreateSubscription(
                    type: $type->value,
                    version: $type->getVersion(),
                    condition: $type->getCondition($broadcasterId),
                    callbackUrl: $callbackUrl,
                    secret: $secret,
                ));

                $results[] = [$type->value, $type->getVersion(), 'created'];
            } catch (RequestException $e) {
                $status = $e->getResponse()->status();
                $message = $status === 403 ? 'missing_scope' : sprintf('error_%d', $status);
                $results[] = [$type->value, $type->getVersion(), $message];
            }
        }

        $this->table(['Type', 'Version', 'Status'], $results);

        $created = count(array_filter($results, fn (array $r): bool => $r[2] === 'created'));
        $this->info(sprintf('%d subscription(s) created.', $created));

        return self::SUCCESS;
    }

    private function clearAllSubscriptions(TwitchHelixConnector $helix, string $broadcasterId): int
    {
        $subscriptions = $this->getExistingSubscriptions($helix, $broadcasterId);

        if ($subscriptions === []) {
            $this->info('No subscriptions found for this broadcaster.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Deleting %d subscription(s)...', count($subscriptions)));

        $results = [];

        foreach ($subscriptions as $sub) {
            try {
                $helix->send(new DeleteSubscription(subscriptionId: $sub['id']));
                $results[] = [$sub['type'], $sub['id'], 'deleted'];
            } catch (RequestException $e) {
                $results[] = [$sub['type'], $sub['id'], sprintf('error_%d', $e->getResponse()->status())];
            }
        }

        $this->table(['Type', 'Subscription ID', 'Status'], $results);

        $deleted = count(array_filter($results, fn (array $r): bool => $r[2] === 'deleted'));
        $this->info(sprintf('%d subscription(s) deleted.', $deleted));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getExistingSubscriptionTypes(TwitchHelixConnector $helix, string $broadcasterId): array
    {
        return collect($this->getExistingSubscriptions($helix, $broadcasterId))
            ->pluck('type')
            ->all();
    }

    /**
     * @return array<int, array{id: string, type: string, condition: array<string, string>}>
     */
    private function getExistingSubscriptions(TwitchHelixConnector $helix, string $broadcasterId): array
    {
        $response = $helix->send(new ListSubscriptions());

        /** @var array<int, array{id: string, type: string, condition: array<string, string>}> $subscriptions */
        $subscriptions = $response->json('data', []);

        return collect($subscriptions)
            ->filter(fn (array $sub): bool => ($sub['condition']['broadcaster_user_id'] ?? null) === $broadcasterId
                || ($sub['condition']['to_broadcaster_user_id'] ?? null) === $broadcasterId)
            ->values()
            ->all();
    }
}
