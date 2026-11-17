<?php

declare(strict_types=1);

use App\Support\Logging\WideEvent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Server\Testing\TestResponse;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->printer()->compact();
pest()->tia()->locally();

pest()->extend(TestCase::class)
    ->group('unit')
    ->in('Unit', '../app-modules/*/tests/Unit');

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->group('feature')
    ->in('Feature', '../app-modules/*/tests/Feature');

pest()->extend(TestCase::class)
    ->group('arch')
    ->in('Arch', '../app-modules/*/tests/Arch');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/**
 * Sends the wide events to memory and returns a reader of the events written so far.
 *
 * @return Closure(): Collection<int, array<string, mixed>>
 */
function fakeWideEvents(): Closure
{
    $handler = fakeLogChannel(WideEvent::CHANNEL);

    return fn (): Collection => collect($handler->getRecords())->map(fn (LogRecord $record): array => $record->context)->values();
}

/**
 * Sends one log channel to memory, so a test can read what it wrote.
 */
function fakeLogChannel(string $channel): TestHandler
{
    config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => TestHandler::class]]);
    Log::forgetChannel($channel);

    return Log::channel($channel)->getLogger()->getHandlers()[0];
}

/**
 * The result of an MCP call as the client receives it: the content, the structured content and isError.
 *
 * @return array<string, mixed>
 */
function mcpResult(TestResponse $response): array
{
    return (fn (): array => $this->response->toArray())->call($response)['result'];
}
