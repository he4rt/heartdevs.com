<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| System-wide architecture rules
|--------------------------------------------------------------------------
|
| These enforce the conventions documented in .ai/guidelines: strict types
| everywhere, no debugging leftovers, no stdClass in domain code, and the
| module dependency direction (domain never depends on presentation).
|
*/

arch('application code declares strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('the admin presentation module declares strict types')
    ->expect('He4rt\PanelAdmin')
    ->toUseStrictTypes();

arch('the app presentation module declares strict types')
    ->expect('He4rt\PanelApp')
    ->toUseStrictTypes();

arch('the panels do not depend on each other')
    ->expect('He4rt\PanelApp')
    ->not->toUse('He4rt\PanelAdmin');

arch('no debugging statements leak into the codebase')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'var_export'])
    ->not->toBeUsed();

arch('only the logging core writes to the log, everything else adds to the wide event')
    ->expect(['App', 'He4rt'])
    ->not->toUse([Log::class, 'logger', 'info'])
    ->ignoring('App\Support\Logging');

arch('the app uses Laravel MCP only in the development server')
    ->expect('App')
    ->not->toUse('Laravel\Mcp')
    ->ignoring('App\Mcp');

arch('a domain module uses Laravel MCP only in its Mcp folder')
    ->expect('He4rt\Identity')
    ->not->toUse('Laravel\Mcp')
    ->ignoring('He4rt\Identity\Mcp');

arch('the panels do not use Laravel MCP')
    ->expect(['He4rt\PanelAdmin', 'He4rt\PanelApp'])
    ->not->toUse('Laravel\Mcp');
