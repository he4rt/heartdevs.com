<?php

declare(strict_types=1);

/*
 * Gate: every module's ServiceProvider lives at `src/<Module>ServiceProvider.php`
 * (the root of src/), never under `src/Providers/`.
 *
 * WHY: `internachi/modular`'s default make:module writes the provider to
 * `src/Providers/...` (see MakeModule::getStubs()). The published stub map in
 * `config/app-modules.php` overrides that destination path via its KEY — if the key
 * drifts back to `src/Providers/...` while the stub declares the ROOT namespace
 * `He4rt\<Module>`, the generated file lands at `src/Providers/X.php` with a root
 * namespace: a silent PSR-4 break (the class never autoloads from that path) that also
 * violates the module-architecture guideline ("Always at
 * src/{ModuleName}ServiceProvider.php. Never in Providers/"). This turns that drift
 * into a CI failure the moment a new module is scaffolded — or an old one regresses.
 *
 * NOTE: `MODULES_DIR` is intentionally NOT declared here — it is a top-level `const`
 * in ModuleTestNamespacesRegisteredTest.php and Pest loads every Arch file into the same
 * process, so redeclaring it would fatal. The literal `app-modules` is inlined instead.
 */

/**
 * Every module paired with the class-name prefix taken from its `src/` PSR-4 namespace
 * (e.g. `He4rt\PanelAdmin\` => `PanelAdmin`), used to derive the expected provider file.
 *
 * @return array<string, array{slug: string, providerFile: string}>
 */
function modulesWithProvider(): array
{
    $modules = [];

    foreach (glob(base_path('app-modules/*/composer.json')) ?: [] as $composerFile) {
        $moduleDir = dirname($composerFile);
        $slug = basename($moduleDir);

        if (!is_dir($moduleDir.'/src')) {
            continue;
        }

        $composer = json_decode((string) file_get_contents($composerFile), associative: true);

        foreach ($composer['autoload']['psr-4'] ?? [] as $prefix => $path) {
            if (mb_rtrim((string) $path, '/') !== 'src') {
                continue;
            }

            $segments = array_values(array_filter(explode('\\', (string) $prefix)));
            $classPrefix = end($segments) ?: $slug;

            $modules[$slug] = [
                'slug' => $slug,
                'providerFile' => $classPrefix.'ServiceProvider.php',
            ];
        }
    }

    ksort($modules);

    return $modules;
}

arch('no module ServiceProvider lives under src/Providers/', function (): void {
    $stray = glob(base_path('app-modules/*/src/Providers/*ServiceProvider.php')) ?: [];

    $relative = array_map(
        static fn (string $path): string => str_replace(base_path().'/', '', $path),
        $stray,
    );

    expect($relative)->toBe([], sprintf(
        '%d ServiceProvider(s) found under `src/Providers/` — move each to the root of '
        ."`src/` and set its namespace to the module root (per module-architecture):\n\n%s\n",
        count($relative),
        implode("\n", $relative),
    ));
});

arch('every module has its ServiceProvider at the root of src/', function (): void {
    $missing = [];

    foreach (modulesWithProvider() as $module) {
        $relativePath = sprintf('app-modules/%s/src/%s', $module['slug'], $module['providerFile']);

        if (is_file(base_path($relativePath))) {
            continue;
        }

        $missing[] = $relativePath;
    }

    expect($missing)->toBe([], sprintf(
        "%d module(s) missing a ServiceProvider at the root of `src/`:\n\n%s\n",
        count($missing),
        implode("\n", $missing),
    ));
});
