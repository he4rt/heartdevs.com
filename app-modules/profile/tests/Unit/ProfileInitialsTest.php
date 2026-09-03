<?php

declare(strict_types=1);

use He4rt\Profile\Support\ProfileInitials;

it('takes the first letter of the first two words', function (): void {
    expect(ProfileInitials::for('Daniel Reis'))->toBe('DR');
});

it('ignores extra whitespace between the words', function (): void {
    expect(ProfileInitials::for('  Daniel   Reis  '))->toBe('DR');
});

it('stops at two letters however long the name is', function (): void {
    expect(ProfileInitials::for('Ana Maria de Souza Lima'))->toBe('AM');
});

it('keeps accented letters', function (): void {
    expect(ProfileInitials::for('Ávila Ñunes'))->toBe('ÁÑ');
});

it('skips words that do not start with a letter', function (): void {
    expect(ProfileInitials::for('42 Daniel 7 Reis'))->toBe('DR');
});
