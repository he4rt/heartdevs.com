<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Actions;

use He4rt\Docs\Discovery\DTOs\DocumentSource;
use InterNACHI\Modular\Support\ModuleRegistry;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Locates every documentation markdown file across the repository: each
 * registered module (CONTEXT/README at its root and everything under its
 * `docs/` directory) plus system-wide documents at the repo root.
 */
final readonly class DiscoverDocumentSourcesAction
{
    public function __construct(
        private ModuleRegistry $modules,
    ) {}

    /**
     * @return list<DocumentSource>
     */
    public function execute(?string $basePath = null): array
    {
        $basePath ??= base_path();

        $sources = [];

        foreach ($this->modules->modules() as $module) {
            $this->collectFromModule($sources, $module->path(), $module->name);
        }

        $this->collectSystemWide($sources, $basePath);

        return $sources;
    }

    /**
     * @param  list<DocumentSource>  $sources
     */
    private function collectFromModule(array &$sources, string $modulePath, string $moduleName): void
    {
        if (is_dir($modulePath)) {
            $root = new Finder()->files()->in($modulePath)->depth(0)->name(['CONTEXT.md', 'README.md']);

            foreach ($root as $file) {
                $sources[] = new DocumentSource($file, $moduleName);
            }
        }

        $docsPath = $modulePath.'/docs';

        if (is_dir($docsPath)) {
            $docs = new Finder()->files()->in($docsPath)->name('*.md');

            foreach ($docs as $file) {
                $sources[] = new DocumentSource($file, $moduleName);
            }
        }
    }

    /**
     * @param  list<DocumentSource>  $sources
     */
    private function collectSystemWide(array &$sources, string $basePath): void
    {
        $contextMap = $basePath.'/CONTEXT-MAP.md';

        if (is_file($contextMap)) {
            $sources[] = new DocumentSource(new SplFileInfo($contextMap), moduleName: null);
        }

        $docsPath = $basePath.'/docs';

        if (is_dir($docsPath)) {
            $finder = new Finder()->files()->in($docsPath)->name('*.md')
                ->path(['introduction', 'adr', 'specs', 'plans', 'prd', 'superpowers']);

            foreach ($finder as $file) {
                $sources[] = new DocumentSource($file, moduleName: null);
            }
        }

        // TODO: o daniel estava trabalhando com um sistema de documentação que provavelmente moraria em resources
        // vou deixar esse path por agora, mas se essa feature for para frente, esse path deve ser removido ou discutido se mantem ele.

        $guidesPath = $basePath.'/resources/docs';

        if (is_dir($guidesPath)) {
            $guides = new Finder()->files()->in($guidesPath)->name('*.md');

            foreach ($guides as $file) {
                $sources[] = new DocumentSource($file, moduleName: null);
            }
        }
    }
}
