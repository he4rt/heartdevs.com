<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Finder\Finder;

/**
 * Loose casts that turn a JSON column into an untyped array/collection.
 * Banned in favour of a dedicated value-object cast (see AsIdentityMetadata).
 *
 * @var list<string>
 */
$bannedExact = [
    'array', 'json', 'object', 'collection',
    'encrypted:array', 'encrypted:collection', 'encrypted:object',
];

/** @var list<class-string> */
$bannedClasses = [
    AsArrayObject::class,
    AsCollection::class,
    AsEnumArrayObject::class,
    AsEnumCollection::class,
    AsEncryptedArrayObject::class,
    AsEncryptedCollection::class,
];

/**
 * Intentional, documented exceptions. Keyed by model class → list of fields.
 * Each entry needs a reason; the goal is to trend this list toward empty.
 *
 * @var array<class-string<Model>, list<string>>
 */
$allowlist = [];

/**
 * Discover every concrete Eloquent model under app/Models and each module's
 * src/**\/Models directory by extracting the namespace + class name from source.
 *
 * @return list<class-string<Model>>
 */
$discoverModels = static function (): array {
    $roots = [base_path('app/Models')];

    foreach (glob(base_path('app-modules/*/src'), GLOB_ONLYDIR) ?: [] as $srcDir) {
        $roots[] = $srcDir;
    }

    $models = [];

    foreach ($roots as $root) {
        if (!is_dir($root)) {
            continue;
        }

        $finder = (new Finder)->files()->in($root)->name('*.php')->path('Models');

        // app/Models sits at the root, so its files have no "Models/" path segment.
        if (str_ends_with($root, 'app/Models')) {
            $finder = (new Finder)->files()->in($root)->name('*.php');
        }

        foreach ($finder as $file) {
            $contents = (string) file_get_contents($file->getRealPath());

            if (!preg_match('/namespace\s+([^;]+);/', $contents, $ns)) {
                continue;
            }

            if (!preg_match('/\bclass\s+(\w+)/', $contents, $class)) {
                continue;
            }

            /** @var class-string $fqcn */
            $fqcn = mb_trim($ns[1]).'\\'.$class[1];

            if (!class_exists($fqcn)) {
                continue;
            }

            $reflection = new ReflectionClass($fqcn);
            if ($reflection->isAbstract()) {
                continue;
            }

            if (!$reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $models[] = $fqcn;
        }
    }

    return array_values(array_unique($models));
};

arch('no model uses a loose array/json cast', function () use ($bannedExact, $bannedClasses, $allowlist, $discoverModels): void {
    $models = $discoverModels();

    expect($models)->not->toBeEmpty('No Eloquent models were discovered — the reflection scan is broken.');

    $violations = [];

    foreach ($models as $model) {
        $allowed = $allowlist[$model] ?? [];

        foreach ((new $model)->getCasts() as $field => $cast) {
            if (in_array($field, $allowed, strict: true)) {
                continue;
            }

            $castString = (string) $cast;
            $normalized = mb_strtolower($castString); // matches 'encrypted:array' & friends verbatim
            $base = strtok($castString, ':'); // drop cast parameters, e.g. "collection:App\Foo"

            if (
                in_array($normalized, $bannedExact, strict: true)
                || in_array($base, $bannedExact, strict: true)
                || in_array($base, $bannedClasses, strict: true)
            ) {
                $violations[] = sprintf('%s::%s => %s', $model, $field, $cast);
            }
        }
    }

    expect($violations)->toBe([], sprintf(
        "Loose array/json casts are banned. Use a value-object cast (see AsIdentityMetadata) or, for an intentional exception, add the field to the allowlist in %s.\nOffenders:\n%s",
        basename(__FILE__),
        implode("\n", $violations),
    ));
});

test('the loose-cast allowlist only references real model attributes', function () use ($allowlist): void {
    $staleEntries = collect($allowlist)
        ->flatMap(fn (array $attributes, string $modelClass): array => collect($attributes)
            ->reject(fn (string $attribute): bool => array_key_exists($attribute, (new $modelClass)->getCasts()))
            ->map(fn (string $attribute): string => $modelClass.'::'.$attribute)
            ->values()
            ->all())
        ->values()
        ->all();

    expect($staleEntries)->toBeEmpty('Remove these allowlist entries: the models no longer cast them.');
});
