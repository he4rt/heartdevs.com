<?php

declare(strict_types=1);

/*
 * Gate: every module's `He4rt\Module\Tests\` namespace must be registered in the
 * ROOT composer.json `autoload-dev`.
 *
 * WHY: Composer never loads the autoload-dev of a dependency, and modular installs
 * each module as a path-repo dependency — so a module's own autoload-dev is inert in
 * the aggregate build. Plain *Test.php files still run (PHPUnit requires them by path),
 * but a shared namespaced class PHPUnit never includes (tests/Support/*, base TestCase,
 * trait, dataset) resolves ONLY through PSR-4 and fatals without the root entry.
 * Not automated by modules:sync; upstream declined (InterNACHI/modular#105). This is
 * the safety net that turns silent drift into a CI failure.
 */

/** Directory (relative to the app root) where modules live. */
const MODULES_DIR = 'app-modules';

/**
 * @return array<string, string> root autoload-dev psr-4: namespace prefix => path
 */
function rootAutoloadDevPsr4(): array
{
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), associative: true);

    return $composer['autoload-dev']['psr-4'] ?? [];
}

/**
 * Every module that ships a `tests/` dir, paired with the test namespace declared in
 * its OWN composer.json autoload-dev (path === 'tests').
 *
 * @return array<string, array{slug: string, namespace: string, expectedPath: string}>
 */
function modulesWithTestNamespace(): array
{
    $modules = [];

    foreach (glob(base_path(MODULES_DIR.'/*/composer.json')) ?: [] as $composerFile) {
        $moduleDir = dirname($composerFile);
        $slug = basename($moduleDir);

        if (!is_dir($moduleDir.'/tests')) {
            continue;
        }

        $composer = json_decode((string) file_get_contents($composerFile), associative: true);

        foreach ($composer['autoload-dev']['psr-4'] ?? [] as $prefix => $path) {
            if (mb_rtrim((string) $path, '/') !== 'tests') {
                continue;
            }

            $modules[$slug] = [
                'slug' => $slug,
                'namespace' => $prefix,
                'expectedPath' => MODULES_DIR.sprintf('/%s/tests/', $slug),
            ];
        }
    }

    ksort($modules);

    return $modules;
}

arch('every module test namespace is registered in the root composer autoload-dev', function (): void {
    $root = rootAutoloadDevPsr4();
    $missing = [];

    foreach (modulesWithTestNamespace() as $module) {
        $registeredPath = $root[$module['namespace']] ?? null;

        if (mb_rtrim((string) $registeredPath, '/') === mb_rtrim($module['expectedPath'], '/')) {
            continue;
        }

        $missing[] = sprintf(
            '            "%s": "%s"',
            addcslashes($module['namespace'], '\\'),
            $module['expectedPath'],
        );
    }

    expect($missing)->toBe([], sprintf(
        "Root composer.json `autoload-dev.psr-4` is missing %d module test namespace(s).\n"
        ."Add the following line(s), then run `composer dump-autoload`:\n\n%s\n",
        count($missing),
        implode(",\n", $missing),
    ));
});
