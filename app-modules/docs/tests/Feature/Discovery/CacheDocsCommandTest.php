<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    config()->set('cache.default', 'array');
    config()->set('docs.cache.enabled', value: true);
    config()->set('docs.cache.ttl', 60);
    Cache::flush();
});

it('warms the documentation cache', function (): void {
    $this->artisan('docs:cache')
        ->assertExitCode(0)
        ->expectsOutputToContain('Documentation cache warmed');

    expect(Cache::has('docs.tree'))->toBeTrue();
});

it('clears the documentation cache', function (): void {
    $this->artisan('docs:cache');

    expect(Cache::has('docs.tree'))->toBeTrue();

    $this->artisan('docs:cache', ['--clear' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('Documentation cache cleared');

    expect(Cache::has('docs.tree'))->toBeFalse();
});
