<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Actions;

use He4rt\Docs\Discovery\Contracts\DocumentTypeStrategyContract;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\DTOs\DocumentSource;
use He4rt\Docs\Discovery\DTOs\DocumentTree;
use He4rt\Docs\Discovery\DTOs\NavigationGroup;
use He4rt\Docs\Discovery\Enums\DocumentTier;
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
        $byTier = [];

        foreach ($documents as $document) {
            $byUrl[$document->url] = $document;
            $byTier[DocumentTier::for($document)->value][] = $document;
        }

        $groups = [];

        foreach (DocumentTier::cases() as $tier) {
            $tiered = $byTier[$tier->value] ?? [];

            if ($tiered === []) {
                continue;
            }

            $groups[] = $tier->groupsByModule()
                ? $this->moduleScopedTier($tier, $tiered)
                : $this->flatTier($tier, $tiered);
        }

        usort($groups, static fn (NavigationGroup $a, NavigationGroup $b): int => $a->order <=> $b->order);

        return new DocumentTree($groups, $byUrl);
    }

    /**
     * @param  list<DiscoveredDocument>  $documents
     */
    private function flatTier(DocumentTier $tier, array $documents): NavigationGroup
    {
        $this->sortDocuments($documents);

        return new NavigationGroup($tier->label(), $tier->icon(), $tier->order(), $documents, tier: $tier);
    }

    /**
     * Engineering tier: docs without a module render directly at the top of the
     * tier, module-scoped docs are sub-grouped alphabetically by module.
     *
     * @param  list<DiscoveredDocument>  $documents
     */
    private function moduleScopedTier(DocumentTier $tier, array $documents): NavigationGroup
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

        $this->sortDocuments($direct);
        ksort($byModule);

        $subgroups = [];

        foreach ($byModule as $module => $list) {
            $this->sortDocuments($list);
            $subgroups[] = new NavigationGroup(Str::headline((string) $module), null, 0, $list, moduleName: (string) $module);
        }

        return new NavigationGroup($tier->label(), $tier->icon(), $tier->order(), $direct, $subgroups, tier: $tier);
    }

    /**
     * Sort by reading order (type, then per-document order), then title.
     *
     * @param  list<DiscoveredDocument>  $documents
     */
    private function sortDocuments(array &$documents): void
    {
        usort(
            $documents,
            static fn (DiscoveredDocument $a, DiscoveredDocument $b): int => [$a->type->readingOrder(), $a->order, $a->title]
                <=> [$b->type->readingOrder(), $b->order, $b->title],
        );
    }
}
