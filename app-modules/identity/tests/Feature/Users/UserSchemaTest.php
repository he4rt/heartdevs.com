<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('keeps the profile columns on the users table', function (): void {
    expect(Schema::hasColumns('identity_users', ['locale', 'theme_color']))->toBeTrue();
});
