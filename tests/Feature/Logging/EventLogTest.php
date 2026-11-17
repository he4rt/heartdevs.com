<?php

declare(strict_types=1);

use App\Support\Logging\EventLog;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->directory = storage_path('framework/testing/event-log-'.uniqid());
    config(['logging.channels.events_daily.path' => $this->directory.'/events.jsonl']);
    File::ensureDirectoryExists($this->directory);

    $this->lines = collect(range(1, 40))->map(fn (int $number): string => json_encode([
        'kind' => 'http',
        'outcome' => 'success',
        'request_id' => "request-{$number}",
        'note' => str_repeat('ação ', $number * 80),
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    File::put("{$this->directory}/events-2026-11-13.jsonl", $this->lines->map(fn (string $line): string => $line."\n")->implode(''));
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

it('stops after its byte budget and says where to continue', function (): void {
    $log = resolve(EventLog::class);
    $everything = fn (array $event): bool => true;

    $first = $log->readBackwards('2026-11-13', olderThan: null, limit: 50, maxBytes: 200, matches: $everything);
    $rest = $log->readBackwards('2026-11-13', $first->olderThan, 50, 1_000_000, $everything);

    expect($first->olderThan)->toBeInt()
        ->and(count($first->lines))->toBeLessThan(40)
        ->and(collect([...$first->lines, ...$rest->lines])->pluck('event.request_id')->all())
        ->toBe(collect(range(40, 1))->map(fn (int $number): string => "request-{$number}")->all())
        ->and($rest->olderThan)->toBeNull();
});

it('finds each event again by the byte offset of its line, also after multibyte text', function (): void {
    $log = resolve(EventLog::class);

    $page = $log->readBackwards('2026-11-13', olderThan: null, limit: 50, maxBytes: 1_000_000, matches: fn (array $event): bool => true);

    collect($page->lines)->each(function ($line) use ($log): void {
        expect($log->lineAt('2026-11-13', $line->offset)?->event)->toBe($line->event);
    });
});

it('reads forward from an event', function (): void {
    $log = resolve(EventLog::class);
    $third = mb_strlen($this->lines->take(2)->map(fn (string $line): string => $line."\n")->implode(''), '8bit');

    $after = $log->readForward('2026-11-13', $third, 2, 1_000_000, fn (array $event): bool => true);

    expect(collect($after)->pluck('event.request_id')->all())->toBe(['request-4', 'request-5']);
});
