<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Console;

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Console\Command;
use Saloon\Exceptions\Request\RequestException;

final class SubscribeTwitchEventsCommand extends Command
{
    protected $signature = 'twitch:subscribe
        {broadcaster_user_id : The Twitch broadcaster user ID}
        {--type= : Subscribe to a specific event type}
        {--all : Subscribe to all available event types}';

    protected $description = 'Create Twitch EventSub webhook subscriptions for a broadcaster';

    public function handle(TwitchHelixConnector $helix): int
    {
        $broadcasterId = $this->argument('broadcaster_user_id');
        $specificType = $this->option('type');

        if (!$specificType && !$this->option('all')) {
            $this->error('Specify --type=<event_type> or --all.');

            return self::FAILURE;
        }

        $existingTypes = $this->getExistingSubscriptions($helix, $broadcasterId);

        $types = $specificType
            ? [TwitchEventSubType::from($specificType)]
            : TwitchEventSubType::cases();

        $callbackUrl = config()->string('services.twitch.eventsub_callback');
        $secret = config()->string('services.twitch.eventsub_secret');

        $results = [];

        foreach ($types as $type) {
            if (in_array($type->value, $existingTypes, true)) {
                $results[] = [$type->value, $type->version(), 'already_exists'];

                continue;
            }

            try {
                $helix->send(new CreateSubscription(
                    type: $type->value,
                    version: $type->version(),
                    condition: $type->condition($broadcasterId),
                    callbackUrl: $callbackUrl,
                    secret: $secret,
                ));

                $results[] = [$type->value, $type->version(), 'created'];
            } catch (RequestException $e) {
                $status = $e->getResponse()->status();
                $message = $status === 403 ? 'missing_scope' : sprintf('error_%d', $status);
                $results[] = [$type->value, $type->version(), $message];
            }
        }

        $this->table(['Type', 'Version', 'Status'], $results);

        $created = count(array_filter($results, fn (array $r): bool => $r[2] === 'created'));
        $this->info(sprintf('%d subscription(s) created.', $created));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getExistingSubscriptions(TwitchHelixConnector $helix, string $broadcasterId): array
    {
        $response = $helix->send(new ListSubscriptions());

        /** @var array<int, array{type: string, condition: array<string, string>}> $subscriptions */
        $subscriptions = $response->json('data', []);

        return collect($subscriptions)
            ->filter(fn (array $sub): bool => ($sub['condition']['broadcaster_user_id'] ?? null) === $broadcasterId
                || ($sub['condition']['to_broadcaster_user_id'] ?? null) === $broadcasterId)
            ->pluck('type')
            ->all();
    }
}
