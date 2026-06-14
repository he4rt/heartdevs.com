<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Actions;

use He4rt\Docs\Discovery\Contracts\DocumentTypeStrategyContract;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\DTOs\DocumentSource;
use He4rt\Docs\Discovery\DTOs\DocumentTree;
use He4rt\Docs\Discovery\DTOs\NavigationGroup;
use He4rt\Docs\Discovery\Enums\DocumentType;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Turns discovered sources into a DocumentTree: classify each file via the
 * first matching strategy, then group/sort into the navigation tree.
 */
final readonly class BuildDocumentTreeAction
{
    /**
     * @param  iterable<DocumentTypeStrategyContract>  $strategies
     */
    public function __construct(
        private DiscoverDocumentSourcesAction $discover,
        private iterable $strategies,
    ) {}

    /**
     * @param  list<DocumentSource>|null  $sources
     */
    public function execute(?array $sources = null): DocumentTree
    {
        $sources ??= $this->discover->execute();

        $documents = [];

        foreach ($sources as $source) {
            $strategy = $this->strategyFor($source->file);

            if (!$strategy instanceof DocumentTypeStrategyContract) {
                continue;
            }

            $document = $strategy->parse($source->file, $source->moduleName);

            if ($document->hidden) {
                continue;
            }

            $documents[] = $document;
        }

        return $this->buildTree($documents);
    }

    private function strategyFor(SplFileInfo $file): ?DocumentTypeStrategyContract
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->matches($file)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * @param  list<DiscoveredDocument>  $documents
     */
    private function buildTree(array $documents): DocumentTree
    {
        $byUrl = [];
        $byType = [];

        foreach ($documents as $document) {
            $byUrl[$document->url] = $document;
            $byType[$document->type->value][] = $document;
        }

        $groups = [];

        foreach (DocumentType::cases() as $type) {
            $typed = $byType[$type->value] ?? [];

            if ($typed === []) {
                continue;
            }

            $this->sortDocuments($typed);

            $groups[] = $type->isModuleScoped()
                ? $this->moduleScopedGroup($type, $typed)
                : new NavigationGroup($type->label(), $type->icon(), $type->order(), $typed);
        }

        usort($groups, static fn (NavigationGroup $a, NavigationGroup $b): int => $a->order <=> $b->order);

        return new DocumentTree($groups, $byUrl);
    }

    /**
     * @param  list<DiscoveredDocument>  $documents
     */
    private function moduleScopedGroup(DocumentType $type, array $documents): NavigationGroup
    {
        $direct = [];
        $byModule = [];

        foreach ($documents as $document) {
            if ($document->moduleName === null) {
                $direct[] = $document;

                continue;
            }

            $byModule[$document->moduleName][] = $document;
        }

        ksort($byModule);

        $subgroups = [];

        foreach ($byModule as $module => $list) {
            $this->sortDocuments($list);
            $subgroups[] = new NavigationGroup(Str::headline((string) $module), null, 0, $list);
        }

        return new NavigationGroup($type->label(), $type->icon(), $type->order(), $direct, $subgroups);
    }

    /**
     * @param  list<DiscoveredDocument>  $documents
     */
    private function sortDocuments(array &$documents): void
    {
        usort($documents, static fn (DiscoveredDocument $a, DiscoveredDocument $b): int => [$a->order, $a->title] <=> [$b->order, $b->title]);
    }
}
