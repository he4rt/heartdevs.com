<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Actions;

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Saloon\Exceptions\Request\RequestException;

final readonly class RegisterTwitchSubscriptionsAction
{
    public function __construct(
        private TwitchHelixConnector $helix,
    ) {}

    /**
     * @param  array<int, TwitchEventSubType>  $types
     * @return array{created: int, skipped: int, failed: int, errors: array<string, string>}
     */
    public function __invoke(string $broadcasterId, array $types = []): array
    {
        if ($types === []) {
            $types = TwitchEventSubType::cases();
        }

        $callbackUrl = $this->resolveCallbackUrl();

        $secret = config()->string('services.twitch.eventsub_secret');

        $existingTypes = TwitchSubscription::query()
            ->where('broadcaster_user_id', $broadcasterId)
            ->where('status', 'enabled')
            ->pluck('type')
            ->all();

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($types as $type) {
            if (in_array($type->value, $existingTypes, strict: true)) {
                $skipped++;

                continue;
            }

            try {
                $response = $this->helix->send(new CreateSubscription(
                    type: $type->value,
                    version: $type->getVersion(),
                    condition: $type->getCondition($broadcasterId),
                    callbackUrl: $callbackUrl,
                    secret: $secret,
                ));

                if (!$response->successful()) {
                    $errors[$type->value] = sprintf('error_%d', $response->status());
                    $failed++;

                    continue;
                }

                $data = $response->json('data.0');

                if (!is_array($data) || !isset($data['id'])) {
                    $errors[$type->value] = 'invalid_response';
                    $failed++;

                    continue;
                }

                TwitchSubscription::query()->updateOrCreate(
                    ['subscription_id' => $data['id']],
                    [
                        'type' => $data['type'] ?? $type->value,
                        'status' => $data['status'] ?? 'enabled',
                        'broadcaster_user_id' => $broadcasterId,
                        'condition' => $data['condition'] ?? $type->getCondition($broadcasterId),
                        'transport' => $data['transport']['method'] ?? 'webhook',
                        'callback_url' => $data['transport']['callback'] ?? $callbackUrl,
                        'cost' => $data['cost'] ?? 0,
                        'version' => $data['version'] ?? $type->getVersion(),
                    ]
                );

                $created++;
            } catch (RequestException $e) {
                $status = $e->getResponse()->status();
                $errors[$type->value] = match ($status) {
                    403 => 'missing_scope',
                    409 => 'already_exists',
                    default => sprintf('error_%d', $status),
                };
                $failed++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors];
    }

    private function resolveCallbackUrl(): string
    {
        $configured = config()->string('services.twitch.eventsub_callback', '');

        if ($configured !== '') {
            return $configured;
        }

        return mb_rtrim(config()->string('app.url'), '/').'/api/webhooks/twitch/eventsub';
    }
}
