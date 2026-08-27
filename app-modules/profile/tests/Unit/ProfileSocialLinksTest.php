<?php

declare(strict_types=1);

use He4rt\Profile\Models\Profile;

test('social links accepts only social platform enum keys', function (): void {
    $profile = new Profile();

    expect(fn () => $profile->social_links = [
        'instagram' => '@dan',
        'invalida' => 'xyz',
    ])->toThrow(InvalidArgumentException::class, 'invalida');
});
