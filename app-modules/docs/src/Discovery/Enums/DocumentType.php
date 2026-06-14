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
     * Whether the sidebar should sub-group documents of this type by module.
     * Only decisions accumulate several entries per module, so only they are
     * sub-grouped; other types render as a flat list.
     */
    public function isModuleScoped(): bool
    {
        return $this === self::Adr;
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
     * Display order of the group in the sidebar.
     */
    public function order(): int
    {
        return match ($this) {
            self::Glossary => 1,
            self::Adr => 2,
            self::Spec => 3,
            self::Plan => 4,
            self::Prd => 5,
            self::Module => 6,
            self::Guide => 7,
        };
    }
}
