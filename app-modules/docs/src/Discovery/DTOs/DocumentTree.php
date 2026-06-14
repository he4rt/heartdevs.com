<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

/**
 * The full documentation navigation tree plus an O(1) URL lookup index.
 */
final readonly class DocumentTree
{
    /**
     * @param  list<NavigationGroup>  $groups
     * @param  array<string, DiscoveredDocument>  $byUrl
     */
    public function __construct(
        public array $groups,
        public array $byUrl,
    ) {}

    /**
     * Resolve a document by its public URL, or null when not found.
     */
    public function find(string $url): ?DiscoveredDocument
    {
        return $this->byUrl[$this->normalize($url)] ?? null;
    }

    /**
     * The first document of the tree, used as the landing target (no landing page).
     */
    public function first(): ?DiscoveredDocument
    {
        foreach ($this->groups as $group) {
            $document = $this->firstOfGroup($group);

            if ($document instanceof DiscoveredDocument) {
                return $document;
            }
        }

        return null;
    }

    /**
     * The sidebar shape consumed by the Blade view.
     *
     * @return list<array{title: string, icon: ?string, tier: ?string, indexable: bool, moduleName: ?string, pages: list<array{title: string, url: string}>, subgroups: list<mixed>}>
     */
    public function toSidebar(): array
    {
        return array_values(array_map(
            static fn (NavigationGroup $group): array => $group->toArray(),
            array_filter($this->groups, static fn (NavigationGroup $group): bool => !$group->isEmpty()),
        ));
    }

    private function firstOfGroup(NavigationGroup $group): ?DiscoveredDocument
    {
        if ($group->documents !== []) {
            return $group->documents[0];
        }

        foreach ($group->subgroups as $subgroup) {
            $document = $this->firstOfGroup($subgroup);

            if ($document instanceof DiscoveredDocument) {
                return $document;
            }
        }

        return null;
    }

    private function normalize(string $url): string
    {
        return '/'.mb_trim($url, '/');
    }
}
