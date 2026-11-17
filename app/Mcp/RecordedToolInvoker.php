<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Mcp\Resources\EventsResource;
use App\Support\Logging\RequestId;
use App\Support\Logging\WideEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Content\StructuredContentText;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\ToolInvoker;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Adds the receipt of the call to the result: the request id and the activity log entries that the call wrote.
 * The model reads the content of a result, not its `_meta`, so the receipt goes into the structured content and its text copy.
 * An exception goes into the wide event, and the result holds only its class and a short message: the event has the rest.
 */
final class RecordedToolInvoker extends ToolInvoker
{
    private const int MAX_MESSAGE_LENGTH = 500;

    private bool $threw = false;

    protected function toErrorResponse(Throwable $e): Response
    {
        if ($e instanceof ValidationException) {
            return parent::toErrorResponse($e);
        }

        resolve(WideEvent::class)->recordError($e);
        $this->threw = true;

        return Response::error(sprintf('%s: %s', class_basename($e), $this->messageOf($e)));
    }

    protected function serializable(Tool $tool): callable
    {
        $serialize = parent::serializable($tool);

        return fn (ResponseFactory $factory): mixed => $serialize($this->withReceipt($factory));
    }

    private function withReceipt(ResponseFactory $factory): ResponseFactory
    {
        $requestId = RequestId::current();

        if ($this->threw) {
            return Response::make([
                ...$factory->responses()->all(),
                Response::text(sprintf('The wide event of this call has the exception and its frames: read %s.', EventsResource::uriFor($requestId))),
            ]);
        }

        $structuredContent = $factory->getStructuredContent();

        if ($structuredContent === null) {
            return $factory;
        }

        $receipt = [...$structuredContent, 'request_id' => $requestId, 'activity' => $this->activity($requestId)];
        $links = $factory->responses()->reject(fn (Response $response): bool => $response->content() instanceof StructuredContentText);

        return Response::make([...Response::structured($receipt)->responses()->all(), ...$links->all()])->withStructuredContent($receipt);
    }

    /**
     * A query error keeps the message of the database and drops the SQL, its bindings and the connection.
     */
    private function messageOf(Throwable $e): string
    {
        $message = $e instanceof QueryException ? $e->getPrevious()?->getMessage() ?? $e->getMessage() : $e->getMessage();

        return Str::limit($message, self::MAX_MESSAGE_LENGTH);
    }

    /**
     * @return array<int, array{event: string|null, subject_type: string|null, subject_id: string|null}>
     */
    private function activity(string $requestId): array
    {
        return Activity::query()
            ->where('request_id', $requestId)
            ->oldest('id')
            ->get()
            ->map(fn (Activity $activity): array => [
                'event' => $activity->event,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id === null ? null : (string) $activity->subject_id,
            ])
            ->values()
            ->all();
    }
}
