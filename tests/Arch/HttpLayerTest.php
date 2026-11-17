<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\Finder\Finder;

/*
 * Gate for .ai/rules/panels.md: a controller of any module only has resource methods and never checks anything,
 * and every form request extends FormRequest directly and declares its own authorize(). A request builds its DTO
 * only through toDto(), and the DTO lives in a domain module.
 */

const RESOURCE_METHODS = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', '__invoke', '__construct'];

const CONTROLLER_CHECKS = '/\b(abort|abort_if|abort_unless|throw_if|throw_unless)\(|Gate::|->validate\(|Validator::make\(|ValidationException/';

const HTTP_MODULES = ['identity', 'panel-admin', 'panel-app'];

/**
 * @return array<string, array{class-string, string}>
 */
function httpClassesIn(string $folder): array
{
    $files = Finder::create()
        ->files()
        ->name('*.php')
        ->in(collect(HTTP_MODULES)->map(fn (string $module): string => dirname(__DIR__, 2).'/app-modules/'.$module.'/src')->all())
        ->path('#Http/'.$folder.'/#');

    return collect($files)
        ->mapWithKeys(function (SplFileInfo $file): array {
            $source = (string) file_get_contents($file->getPathname());
            preg_match('/^namespace ([^;]+);/m', $source, $namespace);

            /** @var class-string $class */
            $class = $namespace[1].'\\'.$file->getBasename('.php');

            return [$class => [$class, $source]];
        })
        ->all();
}

test('a controller is final readonly and has only resource methods', function (string $class): void {
    $reflection = new ReflectionClass($class);

    $customMethods = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $class)
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->diff(RESOURCE_METHODS)
        ->values()
        ->all();

    expect($reflection->isFinal())->toBeTrue("{$class} is not final")
        ->and($reflection->isReadOnly())->toBeTrue("{$class} is not readonly")
        ->and($customMethods)->toBe([], "{$class} has custom public methods");
})->with(fn (): array => collect(httpClassesIn('Controllers'))->map(fn (array $entry): array => [$entry[0]])->all());

test('a controller never checks anything', function (string $class, string $source): void {
    expect(preg_match(CONTROLLER_CHECKS, $source))->toBe(0, "{$class} checks something");
})->with(fn (): array => httpClassesIn('Controllers'));

test('a form request extends FormRequest directly and declares its own authorize()', function (string $class): void {
    $reflection = new ReflectionClass($class);

    expect($reflection->isFinal())->toBeTrue("{$class} is not final")
        ->and(get_parent_class($class))->toBe(FormRequest::class)
        ->and($reflection->getMethod('authorize')->getDeclaringClass()->getName())->toBe($class);
})->with(fn (): array => collect(httpClassesIn('Requests'))
    ->filter(fn (array $entry): bool => class_exists($entry[0]))
    ->map(fn (array $entry): array => [$entry[0]])
    ->all());

test('a form request builds its DTO only through toDto()', function (string $class): void {
    $dtoBuilders = collect(new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => str_contains((string) $method->getReturnType(), '\\DTOs\\'))
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->reject(fn (string $name): bool => $name === 'toDto')
        ->values()
        ->all();

    expect($dtoBuilders)->toBe([], "{$class} builds a DTO through another name");
})->with(fn (): array => collect(httpClassesIn('Requests'))
    ->filter(fn (array $entry): bool => class_exists($entry[0]))
    ->map(fn (array $entry): array => [$entry[0]])
    ->all());

test('a panel never declares a DTO', function (): void {
    $panelDtoFolders = Finder::create()
        ->directories()
        ->in(dirname(__DIR__, 2).'/app-modules/panel-*/src')
        ->name('DTOs');

    expect(iterator_count($panelDtoFolders))->toBe(0);
});
