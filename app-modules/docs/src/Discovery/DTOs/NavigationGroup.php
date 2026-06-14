<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

/**
 * A node in the sidebar navigation tree (Composite). A group holds direct
 * documents and/or nested subgroups (e.g. a type grouped by module).
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
    ) {}

    public function isEmpty(): bool
    {
        if ($this->documents !== []) {
            return false;
        }

        return array_all($this->subgroups, fn ($subgroup) => $subgroup->isEmpty());
    }

    /**
     * Flatten this group into the array shape the sidebar view consumes.
     *
     * @return array{title: string, icon: ?string, pages: list<array{title: string, url: string}>, subgroups: list<array{title: string, icon: ?string, pages: list<array{title: string, url: string}>, subgroups: list<mixed>}>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'icon' => $this->icon,
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
