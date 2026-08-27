<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

use He4rt\Docs\Discovery\Enums\DocumentTier;

/**
 * A node in the sidebar navigation tree (Composite). A group holds direct
 * documents and/or nested subgroups (e.g. a type grouped by module).
 *
 * A tier group (top-level) carries its `tier`; a module subgroup carries the
 * `moduleName` so the view can color its dot deterministically.
 */
final readonly class NavigationGroup
{
    /**
     * @param  list<DiscoveredDocument>  $documents
     * @param  list<NavigationGroup>  $subgroups
     */
    public function __construct(
        public string $title,
        public ?string $icon = null,
        public int $order = 0,
        public array $documents = [],
        public array $subgroups = [],
        public ?DocumentTier $tier = null,
        public ?string $moduleName = null,
    ) {}

    public function isEmpty(): bool
    {
        if ($this->documents !== []) {
            return false;
        }

        return array_all($this->subgroups, fn (NavigationGroup $subgroup) => $subgroup->isEmpty());
    }

    /**
     * Flatten this group into the array shape the sidebar view consumes.
     *
     * @return array{title: string, icon: ?string, tier: ?string, indexable: bool, moduleName: ?string, pages: list<array{title: string, url: string}>, subgroups: list<array{title: string, icon: ?string, tier: ?string, indexable: bool, moduleName: ?string, pages: list<array{title: string, url: string}>, subgroups: list<mixed>}>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'icon' => $this->icon,
            'tier' => $this->tier?->value,
            'indexable' => $this->tier?->isIndexable() ?? true,
            'moduleName' => $this->moduleName,
            'pages' => array_map(
                static fn (DiscoveredDocument $doc): array => ['title' => $doc->title, 'url' => $doc->url],
                $this->documents,
            ),
            'subgroups' => array_map(
                static fn (NavigationGroup $group): array => $group->toArray(),
                $this->subgroups,
            ),
        ];
    }
}
