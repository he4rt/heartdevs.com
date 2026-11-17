<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
 * Filament and Livewire are gone. This keeps them, and the plugins that came with them, from coming back.
 * Pest's toUse() matches vendor classes by exact name only, so this test reads the source.
 */

/**
 * @param  list<string>  $paths
 * @return list<string> "file: offending line"
 */
function filamentReferencesIn(array $paths): array
{
    $vendors = 'Filament|Livewire|AlizHarb|Basement\\\\BetterMails|Guava\\\\FilamentKnowledgeBase|Joaopaulolndev|STS\\\\FilamentImpersonate';
    $pattern = sprintf('/^.*(?:\\\\?\\b(?:%s)\\\\|\\bfilament\\(\\)|\\blivewire\\().*$/m', $vendors);
    $references = [];

    foreach (Finder::create()->files()->name('*.php')->in(array_map(base_path(...), $paths)) as $file) {
        preg_match_all($pattern, $file->getContents(), $matches);

        foreach ($matches[0] as $line) {
            $references[] = sprintf('%s: %s', str_replace(base_path().'/', '', $file->getPathname()), mb_trim($line));
        }
    }

    return $references;
}

arch('the domain does not depend on Filament or Livewire', function (): void {
    expect(filamentReferencesIn([
        'app-modules/identity/src',
        'app-modules/identity/database',
        'app/Contracts',
        'app/Data',
        'app/Enums',
        'app/Http',
        'app/Listeners',
        'app/Models',
        'app/Support',
        'database/migrations',
        'database/seeders',
    ]))->toBeEmpty();
});

arch('the Inertia apps do not depend on Filament or Livewire', function (): void {
    expect(filamentReferencesIn([
        'app-modules/panel-admin/src',
        'app-modules/panel-admin/routes',
        'app-modules/panel-app/src',
        'app-modules/panel-app/routes',
    ]))->toBeEmpty();
});
