<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use App\Support\Logging\RequestId;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Stamps an activity with the request that caused it. Entries from one request share the {@see RequestId}
 * of its wide event, and an activity written by a job keeps the id of the request that dispatched it.
 */
final readonly class ActivityContext
{
    public function __construct(private Request $request) {}

    public function apply(Activity $activity): void
    {
        $requestId = RequestId::current();
        $context = collect([
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'request_id' => $requestId,
            'method' => $this->request->method(),
            'path' => $this->request->path(),
            'route' => $this->request->route()?->getName(),
        ])->filter(fn (mixed $value): bool => $value !== null && $value !== '')->all();

        $activity->properties = ($activity->properties ?? collect())->merge($context);
        $activity->setAttribute('ip_address', $context['ip_address'] ?? null);
        $activity->setAttribute('request_id', $requestId);
    }
}
