<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Support\Logging\EventLog;
use App\Support\Logging\EventLogLine;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name(value: 'events')]
#[Description(value: 'The wide events of one request id, newest first: the call of a tool and the jobs it dispatched. Read it to see the queries, the duration and the exception of a call.')]
#[MimeType(value: 'application/json')]
final class EventsResource extends Resource implements HasUriTemplate
{
    private const int MAX_EVENTS = 20;

    private const int SCAN_BYTES = 5_242_880;

    private const int DAYS = 2;

    public static function uriFor(string $requestId): string
    {
        return "development://events/{$requestId}";
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate(self::uriFor('{requestId}'));
    }

    public function handle(Request $request, EventLog $log): Response
    {
        $requestId = $request->string('requestId')->toString();

        $events = collect($log->days())
            ->take(self::DAYS)
            ->flatMap(fn (string $day): array => $log->readBackwards(
                $day,
                olderThan: null,
                limit: self::MAX_EVENTS,
                maxBytes: self::SCAN_BYTES,
                matches: fn (array $event): bool => ($event['request_id'] ?? null) === $requestId,
            )->lines)
            ->map(fn (EventLogLine $line): array => $line->event)
            ->take(self::MAX_EVENTS)
            ->values()
            ->all();

        if ($events === []) {
            return Response::error(sprintf('No wide event has the request id [%s] in the last %d days. The app writes the event of a call when the call ends.', $requestId, self::DAYS));
        }

        return Response::json($events);
    }
}
