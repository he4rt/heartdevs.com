<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\Enums\DocumentTier;
use He4rt\Docs\Discovery\Enums\DocumentType;

function tierDoc(DocumentType $type, ?string $module = null): DiscoveredDocument
{
    return new DiscoveredDocument(
        type: $type,
        absolutePath: '/abs/x.md',
        slug: 'x',
        url: '/docs/'.$type->value.'/x',
        title: 'X',
        moduleName: $module,
    );
}

describe('DocumentTier', static function (): void {
    it('exposes label, icon and order for every case', function (): void {
        foreach (DocumentTier::cases() as $tier) {
            expect($tier->label())->not->toBeEmpty()
                ->and($tier->icon())->not->toBeEmpty()
                ->and($tier->order())->toBeGreaterThan(0);
        }
    });

    it('orders the tiers from intro to engineering', function (): void {
        expect(DocumentTier::Introduction->order())->toBe(1)
            ->and(DocumentTier::GettingStarted->order())->toBe(2)
            ->and(DocumentTier::Engineering->order())->toBe(3);
    });

    it('only indexes the curated entry-point tiers', function (): void {
        expect(DocumentTier::Introduction->isIndexable())->toBeTrue()
            ->and(DocumentTier::GettingStarted->isIndexable())->toBeTrue()
            ->and(DocumentTier::Engineering->isIndexable())->toBeFalse();
    });

    it('groups only the engineering tier by module', function (): void {
        expect(DocumentTier::Engineering->groupsByModule())->toBeTrue()
            ->and(DocumentTier::Introduction->groupsByModule())->toBeFalse()
            ->and(DocumentTier::GettingStarted->groupsByModule())->toBeFalse();
    });
});

describe('DocumentType::tier', static function (): void {
    it('maps the guide type to getting started', function (): void {
        expect(DocumentType::Guide->tier())->toBe(DocumentTier::GettingStarted);
    });

    it('maps every reference type to engineering', function (): void {
        foreach ([DocumentType::Glossary, DocumentType::Adr, DocumentType::Spec, DocumentType::Plan, DocumentType::Prd, DocumentType::Module] as $type) {
            expect($type->tier())->toBe(DocumentTier::Engineering);
        }
    });
});

describe('DocumentTier::for', static function (): void {
    it('places the context map (module-less glossary) in getting started', function (): void {
        expect(DocumentTier::for(tierDoc(DocumentType::Glossary)))->toBe(DocumentTier::GettingStarted);
    });

    it('keeps a module-scoped glossary in engineering', function (): void {
        expect(DocumentTier::for(tierDoc(DocumentType::Glossary, 'moderation')))->toBe(DocumentTier::Engineering);
    });

    it('places a guide in getting started', function (): void {
        expect(DocumentTier::for(tierDoc(DocumentType::Guide, 'moderation')))->toBe(DocumentTier::GettingStarted);
    });

    it('places reference types in engineering', function (): void {
        expect(DocumentTier::for(tierDoc(DocumentType::Adr, 'moderation')))->toBe(DocumentTier::Engineering)
            ->and(DocumentTier::for(tierDoc(DocumentType::Module, 'moderation')))->toBe(DocumentTier::Engineering)
            ->and(DocumentTier::for(tierDoc(DocumentType::Spec, 'moderation')))->toBe(DocumentTier::Engineering);
    });
});
