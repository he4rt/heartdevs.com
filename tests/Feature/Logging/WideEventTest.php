<?php

declare(strict_types=1);

use App\Support\Logging\WideEvent;
use App\Support\Logging\WideEventKind;

beforeEach(function (): void {
    $this->events = fakeWideEvents();
});

it('ignores a field added outside a unit of work', function (): void {
    WideEvent::add('checkout.total_cents', 42_000);

    resolve(WideEvent::class)->finish();

    expect(($this->events)())->toBeEmpty();
});

it('groups dotted keys and redacts a sensitive one', function (): void {
    $event = resolve(WideEvent::class);
    $event->start(WideEventKind::Http);

    WideEvent::add('checkout.total_cents', 42_000);
    WideEvent::add('gateway.api_key', 'sk_live_123');
    $event->finish();

    expect(($this->events)()->sole())
        ->checkout->toBe(['total_cents' => 42_000])
        ->gateway->toBe(['api_key' => '[REDACTED]']);
});

it('keeps one event when a unit starts inside another', function (): void {
    $event = resolve(WideEvent::class);

    expect($event->start(WideEventKind::Http))->toBeTrue()
        ->and($event->start(WideEventKind::Command))->toBeFalse()
        ->and($event->kind())->toBe(WideEventKind::Http);
});
