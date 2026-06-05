<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\Webhook;

use He4rt\IntegrationGithub\Models\GithubEventLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class GithubWebhookController
{
    public function __construct(
        private ProjectGithubEvent $projector,
    ) {}

    public function __invoke(Request $request): Response
    {
        $event = (string) $request->header('X-GitHub-Event', '');
        $delivery = $request->header('X-GitHub-Delivery');

        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $log = GithubEventLog::query()->firstOrCreate(
            ['delivery_id' => $delivery],
            [
                'event_type' => $event,
                'repo' => $this->stringOrNull($payload, 'repository.full_name'),
                'actor_login' => $this->stringOrNull($payload, 'sender.login'),
                'payload' => $payload,
            ],
        );

        if ($log->wasRecentlyCreated) {
            $this->projector->execute($event, $payload);
        }

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stringOrNull(array $payload, string $path): ?string
    {
        $value = data_get($payload, $path);

        return is_string($value) ? $value : null;
    }
}
