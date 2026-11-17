<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Queries;

use He4rt\Identity\Users\DTOs\BrowserSession;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Sessions live in the database only with the database driver. Other drivers list nothing.
 */
final class BrowserSessions
{
    private const array BROWSERS = ['Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'];

    private const array PLATFORMS = ['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'];

    /** @return array<int, BrowserSession> */
    public function for(User $user, string $currentSessionId): array
    {
        if (!$this->usesDatabase()) {
            return [];
        }

        $rows = DB::connection(config('session.connection'))
            ->table(config()->string('session.table'))
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        return $rows->map(function (object $session) use ($currentSessionId): BrowserSession {
            $row = (array) $session;
            $userAgent = is_string($row['user_agent'] ?? null) ? $row['user_agent'] : '';
            $ipAddress = $row['ip_address'] ?? null;
            $lastActivity = $row['last_activity'] ?? 0;

            return new BrowserSession(
                browser: $this->match($userAgent, self::BROWSERS),
                platform: $this->match($userAgent, self::PLATFORMS),
                ipAddress: is_string($ipAddress) ? $ipAddress : null,
                lastActiveAt: Date::createFromTimestamp(is_numeric($lastActivity) ? (int) $lastActivity : 0)->toIso8601String(),
                isCurrent: ($row['id'] ?? null) === $currentSessionId,
            );
        })->values()->all();
    }

    private function usesDatabase(): bool
    {
        return config('session.driver') === 'database';
    }

    /** @param  array<string, string>  $needles */
    private function match(string $userAgent, array $needles): string
    {
        foreach ($needles as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                return $label;
            }
        }

        return 'Unknown';
    }
}
