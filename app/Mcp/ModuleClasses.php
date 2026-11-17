<?php

declare(strict_types=1);

namespace App\Mcp;

use Illuminate\Support\Collection;
use InterNACHI\Modular\Support\ModuleConfig;
use InterNACHI\Modular\Support\ModuleRegistry;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Finds the classes in one folder of every module, so a new file there is enough to add a tool or a factory.
 */
final readonly class ModuleClasses
{
    public function __construct(private ModuleRegistry $registry) {}

    /**
     * @return Collection<int, string> the fully qualified class names, sorted
     */
    public function in(string $folder, string $fileName): Collection
    {
        return $this->registry->modules()
            ->map(fn (ModuleConfig $module): string => $module->path($folder))
            ->filter(fn (string $path): bool => is_dir($path))
            ->flatMap(fn (string $path): Collection => collect(Finder::create()->files()->name($fileName)->in($path))
                ->map(fn (SplFileInfo $file): string => $this->registry->moduleForPathOrFail($file->getPathname())->pathToFullyQualifiedClassName($file->getPathname())))
            ->sort()
            ->values();
    }
}
