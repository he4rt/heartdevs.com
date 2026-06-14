<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Enums;

use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;

/**
 * The top-level reading tier a document belongs to. Tiers drive both the
 * sidebar grouping and SEO indexability: Introduction and Getting Started are
 * curated, indexable entry points, while Engineering is the internal,
 * noindex reference material grouped by module.
 */
enum DocumentTier: string
{
    case Introduction = 'introduction';
    case GettingStarted = 'getting-started';
    case Engineering = 'engineering';

    /**
     * Resolve the tier a document belongs to.
     *
     * The CONTEXT-MAP — a Glossary with no module — is the system-wide
     * overview and belongs to Getting Started, while a module-scoped
     * Glossary stays in Engineering.
     */
    public static function for(DiscoveredDocument $document): self
    {
        if ($document->type === DocumentType::Glossary && $document->moduleName === null) {
            return self::GettingStarted;
        }

        return $document->type->tier();
    }

    /**
     * Human-facing label for the navigation group (pt_BR).
     */
    public function label(): string
    {
        return match ($this) {
            self::Introduction => 'Introdução',
            self::GettingStarted => 'Getting Started',
            self::Engineering => 'Engenharia',
        };
    }

    /**
     * Heroicon name used by the Flux sidebar group heading.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Introduction => 'book-open',
            self::GettingStarted => 'rocket-launch',
            self::Engineering => 'wrench-screwdriver',
        };
    }

    /**
     * Display order of the tier in the sidebar (lower comes first).
     */
    public function order(): int
    {
        return match ($this) {
            self::Introduction => 1,
            self::GettingStarted => 2,
            self::Engineering => 3,
        };
    }

    /**
     * Whether documents in this tier should be indexed by search engines.
     * Engineering material is internal reference and is served as noindex.
     */
    public function isIndexable(): bool
    {
        return match ($this) {
            self::Introduction, self::GettingStarted => true,
            self::Engineering => false,
        };
    }

    /**
     * Whether the sidebar should sub-group this tier's documents by module.
     */
    public function groupsByModule(): bool
    {
        return $this === self::Engineering;
    }
}
