<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * `Inertia::render('users/index')` resolves `resources/js/features/** /users/pages/index.tsx`.
 *
 * @return list<string>
 */
function renderedAdminComponents(): array
{
    $components = [];

    foreach (Finder::create()->files()->in(base_path('app-modules/panel-admin/src'))->name('*.php') as $file) {
        preg_match_all("/Inertia::render\\(\\s*'([^']+)'/", $file->getContents(), $matches);
        array_push($components, ...$matches[1]);
    }

    return array_values(array_unique($components));
}

arch('every rendered admin page has a page component', function (): void {
    $featuresPath = base_path('app-modules/panel-admin/resources/js/features');

    $missing = array_values(array_filter(renderedAdminComponents(), function (string $component) use ($featuresPath): bool {
        $directory = dirname($component);
        $page = basename($component);

        return glob(sprintf('%s/{,*/}%s/pages/%s.tsx', $featuresPath, $directory, $page), GLOB_BRACE) === [];
    }));

    expect(renderedAdminComponents())->not->toBeEmpty()
        ->and($missing)->toBeEmpty();
});
