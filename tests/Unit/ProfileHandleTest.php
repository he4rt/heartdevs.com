<?php

declare(strict_types=1);

use App\Support\ProfileHandle;

it('prefixes a bare handle with the platform base', function (): void {
    expect(ProfileHandle::url('https://github.com/', 'gabrielfvdev'))
        ->toBe('https://github.com/gabrielfvdev');
});

it('drops the leading at sign', function (): void {
    expect(ProfileHandle::url('https://x.com/', '@gabrielfvdev'))
        ->toBe('https://x.com/gabrielfvdev');
});

it('trims the handle before building the url', function (): void {
    expect(ProfileHandle::url('https://dev.to/', '  @danielhe4rt  '))
        ->toBe('https://dev.to/danielhe4rt');
});

it('keeps a handle that is already an absolute url', function (string $url): void {
    expect(ProfileHandle::url('https://github.com/', $url))->toBe($url);
})->with([
    'https' => 'https://github.com/he4rt',
    'http' => 'http://example.com/perfil',
]);

it('trims an absolute url too', function (): void {
    expect(ProfileHandle::url('https://github.com/', ' https://github.com/he4rt '))
        ->toBe('https://github.com/he4rt');
});
