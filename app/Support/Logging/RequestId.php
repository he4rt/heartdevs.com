<?php

declare(strict_types=1);

namespace App\Support\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * The id that links a request, the jobs it dispatches, its activity log entries and its wide events.
 * It lives in the Laravel Context, so a queued job and a scheduled command receive it.
 */
final class RequestId
{
    public const string CONTEXT_KEY = 'request_id';

    private const int MAX_LENGTH = 100;

    public static function current(): string
    {
        $id = Context::get(self::CONTEXT_KEY);

        if (is_string($id)) {
            return $id;
        }

        return self::begin(request());
    }

    public static function begin(Request $request): string
    {
        $id = self::fromHeader($request) ?? Str::uuid7()->toString();
        Context::add(self::CONTEXT_KEY, $id);

        return $id;
    }

    private static function fromHeader(Request $request): ?string
    {
        $header = Str::substr($request->headers->get('X-Request-Id') ?? '', 0, self::MAX_LENGTH);

        return preg_match('/\A[A-Za-z0-9._-]+\z/', $header) === 1 ? $header : null;
    }
}
