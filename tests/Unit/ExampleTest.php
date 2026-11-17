<?php

declare(strict_types=1);

test('that false is false', function (): void {
    expect(value: false)->toBeFalse();
});
