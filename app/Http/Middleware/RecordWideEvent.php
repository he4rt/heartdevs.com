<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Logging\RequestId;
use App\Support\Logging\WideEvent;
use App\Support\Logging\WideEventKind;
use Closure;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\Impersonation\Impersonation;
use He4rt\Identity\Users\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens the wide event of a request and writes it after the response is sent.
 * The event names the route by its template, never by the raw path or the query string: both can carry a token.
 */
final readonly class RecordWideEvent
{
    private const string HEALTH_CHECK_PATH = 'up';

    private const int MAX_PATH_LENGTH = 200;

    public function __construct(private WideEvent $event) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(self::HEALTH_CHECK_PATH)) {
            return $next($request);
        }

        $requestId = RequestId::begin($request);
        $this->event->start(WideEventKind::Http);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!$this->event->isOpen()) {
            return;
        }

        $route = $request->route();
        $matchedRoute = $route instanceof Route ? $route : null;

        $this->event->set('http.method', $request->method());
        $this->event->set('http.route', $matchedRoute?->getName());
        $this->event->set('http.uri', $matchedRoute?->uri());
        $this->event->set('http.status', $response->getStatusCode());
        $this->event->set('http.inertia', $request->headers->has('X-Inertia'));

        if (!$matchedRoute instanceof Route) {
            $this->event->set('http.path', Str::limit($request->path(), self::MAX_PATH_LENGTH, ''));
        }

        $this->recordWho($request, $matchedRoute);
        $this->event->finish();
    }

    private function recordWho(Request $request, ?Route $route): void
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->event->identify($user);
        }

        if ($request->hasSession()) {
            $this->event->set('impersonator.id', resolve(Impersonation::class)->impersonatorId());
        }

        $team = collect($route?->parameters() ?? [])->first(fn (mixed $parameter): bool => $parameter instanceof Team);

        if ($team instanceof Team && !array_key_exists('team.id', $this->event->fields())) {
            $this->event->set('team.id', $team->id);
        }
    }
}
