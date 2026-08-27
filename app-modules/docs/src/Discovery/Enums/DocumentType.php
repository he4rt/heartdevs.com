<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Enums;

/**
 * The kind of documentation a file represents.
 *
 * The backing value is the URL section segment (e.g. `/docs/decisions/...`),
 * so this enum is the single source of truth for routing.
 */
enum DocumentType: string
{
    case Introduction = 'introduction';
    case Glossary = 'glossary';
    case Adr = 'decisions';
    case Spec = 'specs';
    case Plan = 'plans';
    case Prd = 'prd';
    case Module = 'modules';
    case Guide = 'guides';

    /**
     * Human-facing label for the navigation group (pt_BR).
     */
    public function label(): string
    {
        return match ($this) {
            self::Introduction => 'Introdução',
            self::Glossary => 'Glossário',
            self::Adr => 'Decisões',
            self::Spec => 'Specs',
            self::Plan => 'Plans',
            self::Prd => 'PRDs',
            self::Module => 'Módulos',
            self::Guide => 'Guias',
        };
    }

    /**
     * Heroicon name used by the Flux sidebar item.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Introduction => 'sparkles',
            self::Glossary => 'book-open',
            self::Adr => 'scale',
            self::Spec => 'document-text',
            self::Plan => 'clipboard-document-list',
            self::Prd => 'rocket-launch',
            self::Module => 'cube',
            self::Guide => 'academic-cap',
        };
    }

    /**
     * Whether documents of this type are dated planning artifacts that should
     * carry a "may not reflect the current state" notice.
     */
    public function isDatedArtifact(): bool
    {
        return match ($this) {
            self::Spec, self::Plan => true,
            default => false,
        };
    }

    /**
     * The reading tier this type maps to. Note the CONTEXT-MAP exception lives
     * in DocumentTier::for(), since it depends on the document's module.
     */
    public function tier(): DocumentTier
    {
        return match ($this) {
            self::Introduction => DocumentTier::Introduction,
            self::Guide => DocumentTier::GettingStarted,
            self::Glossary, self::Adr, self::Spec, self::Plan, self::Prd, self::Module => DocumentTier::Engineering,
        };
    }

    /**
     * Order in which a type is read inside a module, lowest first. Drives the
     * per-module ordering of the Engineering tier in the sidebar.
     */
    public function readingOrder(): int
    {
        return match ($this) {
            self::Introduction => 0,
            self::Module => 0,
            self::Glossary => 1,
            self::Adr => 2,
            self::Spec => 3,
            self::Plan => 4,
            self::Prd => 5,
            self::Guide => 6,
        };
    }
}
