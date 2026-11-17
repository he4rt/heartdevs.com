<?php

declare(strict_types=1);

namespace App\Support\Logging;

use App\Support\Activitylog\ActivityRedactor;
use App\Support\FeatureFlags\FeatureFlag;
use App\Support\FeatureFlags\FeatureScope;
use He4rt\Identity\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The one event of the current unit of work: a request, a job, a command or a scheduled task.
 * Code adds fields while the unit runs, and {@see finish()} writes the event once, if {@see TailSampler} keeps it.
 * A unit that starts inside another unit adds to the outer event.
 */
final class WideEvent
{
    public const string CHANNEL = 'events';

    private const int MAX_STRING_LENGTH = 500;

    /** @var array<string, scalar|null> */
    private array $fields = [];

    private ?WideEventKind $kind = null;

    private float $startedAt = 0.0;

    private ?User $user = null;

    private bool $readAnActiveScopedFlag = false;

    private ?Throwable $lastRecordedError = null;

    public static function add(string $key, string|int|float|bool|null $value): void
    {
        resolve(self::class)->set($key, $value);
    }

    /**
     * @return bool false when another unit is already open, so the caller must not finish it
     */
    public function start(WideEventKind $kind): bool
    {
        if ($this->isOpen()) {
            return false;
        }

        $this->kind = $kind;
        $this->startedAt = microtime(as_float: true);
        $this->user = null;
        $this->readAnActiveScopedFlag = false;
        $this->fields = [];

        $this->set('timestamp', now()->toIso8601ZuluString('millisecond'));
        $this->set('kind', $kind->value);
        $this->set('event_id', Str::uuid7()->toString());
        $this->set('request_id', RequestId::current());
        $this->set('service', config()->string('app.name'));
        $this->set('environment', config()->string('app.env'));
        $this->set('version', config()->string('logging.wide_events.version'));

        return true;
    }

    public function isOpen(): bool
    {
        return $this->kind instanceof WideEventKind;
    }

    public function set(string $key, string|int|float|bool|null $value): void
    {
        if (!$this->isOpen()) {
            return;
        }

        $this->fields[$key] = match (true) {
            ActivityRedactor::isSensitive(Str::afterLast($key, '.')) => ActivityRedactor::REDACTED,
            is_string($value) => Str::limit($value, self::MAX_STRING_LENGTH, ''),
            default => $value,
        };
    }

    public function identify(User $user): void
    {
        $this->user = $user;
        $this->set('user.id', $user->id);
    }

    public function recordQuery(float $durationMs): void
    {
        if (!$this->isOpen()) {
            return;
        }

        $this->fields['db.queries'] = $this->integer('db.queries') + 1;
        $this->fields['db.duration_ms'] = round($this->number('db.duration_ms') + $durationMs, 2);
    }

    public function recordFlag(FeatureFlag $flag, bool $active): void
    {
        $this->set("feature_flags.{$flag->key()}", $active);

        if ($active && $flag->scope() !== FeatureScope::Global) {
            $this->readAnActiveScopedFlag = true;
        }
    }

    /**
     * The exception handler calls this for every reported exception.
     *
     * @return false|null false when the event holds the exception, so the default log line is skipped
     */
    public function recordError(Throwable $exception): ?false
    {
        if ($exception === $this->lastRecordedError) {
            return false;
        }

        if (!$this->isOpen()) {
            return null;
        }

        $count = $this->integer('error.count');

        if ($count === 0) {
            collect(ExceptionFields::of($exception))->each(fn (string|int|float|bool|null $value, string $key) => $this->set("error.{$key}", $value));
        }

        $this->fields['error.count'] = $count + 1;
        $this->lastRecordedError = $exception;

        return false;
    }

    public function finish(): void
    {
        if (!$this->kind instanceof WideEventKind) {
            return;
        }

        $kind = $this->kind;
        $this->set('duration_ms', round((microtime(as_float: true) - $this->startedAt) * 1_000, 1));
        $this->set('memory_peak_mb', (int) round(memory_get_peak_usage(real_usage: true) / 1_048_576));
        $this->set('outcome', $this->outcome()->value);

        $keptBecause = resolve(TailSampler::class)->keepReason($this);

        if ($keptBecause !== null) {
            $this->set('sampling.kept_because', $keptBecause);
            Log::channel(self::CHANNEL)->info($kind->value, Arr::undot($this->fields));
        }

        $this->kind = null;
    }

    public function kind(): ?WideEventKind
    {
        return $this->kind;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function durationMs(): float
    {
        return $this->number('duration_ms');
    }

    public function readAnActiveScopedFlag(): bool
    {
        return $this->readAnActiveScopedFlag;
    }

    public function failed(): bool
    {
        return $this->integer('error.count') > 0
            || $this->integer('http.status') >= 500
            || $this->integer('command.exit_code') !== 0;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    private function outcome(): WideEventOutcome
    {
        return match (true) {
            $this->failed() => WideEventOutcome::Error,
            $this->integer('http.status') >= 400, ($this->fields['mcp.is_error'] ?? false) === true => WideEventOutcome::ClientError,
            default => WideEventOutcome::Success,
        };
    }

    private function integer(string $key): int
    {
        $value = $this->fields[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }

    private function number(string $key): float
    {
        $value = $this->fields[$key] ?? 0;

        return is_int($value) || is_float($value) ? $value : 0.0;
    }
}
