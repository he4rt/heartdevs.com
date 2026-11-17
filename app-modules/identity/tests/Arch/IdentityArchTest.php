<?php

declare(strict_types=1);

use Illuminate\Contracts\Support\Arrayable;

/*
|--------------------------------------------------------------------------
| Identity module architecture rules
|--------------------------------------------------------------------------
|
| Module-scoped conventions: the domain stays free of the panels, and its
| actions and DTOs are final readonly classes.
|
*/

arch('the identity domain module declares strict types')
    ->expect('He4rt\Identity')
    ->toUseStrictTypes();

arch('domain code never produces a stdClass')
    ->expect('He4rt\Identity')
    ->not->toUse('stdClass');

arch('domain modules do not depend on the presentation layer')
    ->expect('He4rt\Identity')
    ->not->toUse(['He4rt\PanelAdmin', 'He4rt\PanelApp']);

arch('actions are final readonly classes')
    ->expect([
        'He4rt\Identity\Permissions\Actions',
        'He4rt\Identity\SocialAccounts\Actions',
        'He4rt\Identity\Teams\Actions',
        'He4rt\Identity\Users\Actions',
    ])
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('DTOs are final readonly classes')
    ->expect([
        'He4rt\Identity\Permissions\DTOs',
        'He4rt\Identity\SocialAccounts\DTOs',
        'He4rt\Identity\Teams\DTOs',
        'He4rt\Identity\Users\DTOs',
    ])
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('DTOs never serialize themselves')
    ->expect([
        'He4rt\Identity\Permissions\DTOs',
        'He4rt\Identity\SocialAccounts\DTOs',
        'He4rt\Identity\Teams\DTOs',
        'He4rt\Identity\Users\DTOs',
    ])
    ->not->toImplement([Arrayable::class, JsonSerializable::class]);
