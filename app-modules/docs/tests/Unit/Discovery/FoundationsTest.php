<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\DTOs\DocumentTree;
use He4rt\Docs\Discovery\DTOs\NavigationGroup;
use He4rt\Docs\Discovery\DTOs\PlanMetadata;
use He4rt\Docs\Discovery\Enums\AdrStatus;
use He4rt\Docs\Discovery\Enums\DocumentType;
use He4rt\Docs\Discovery\Enums\PlanStatus;

function docsDtoFixture(string $url = '/docs/decisions/x', string $title = 'X'): DiscoveredDocument
{
    return new DiscoveredDocument(
        type: DocumentType::Adr,
        absolutePath: '/abs'.$url.'.md',
        slug: 'x',
        url: $url,
        title: $title,
    );
}

describe('DocumentType', function (): void {
    it('uses the URL section segment as backing value', function (): void {
        expect(DocumentType::Adr->value)->toBe('decisions')
            ->and(DocumentType::tryFrom('decisions'))->toBe(DocumentType::Adr)
            ->and(DocumentType::tryFrom('3.x'))->toBeNull();
    });

    it('sub-groups only decisions by module', function (): void {
        expect(DocumentType::Adr->isModuleScoped())->toBeTrue()
            ->and(DocumentType::Glossary->isModuleScoped())->toBeFalse()
            ->and(DocumentType::Module->isModuleScoped())->toBeFalse()
            ->and(DocumentType::Spec->isModuleScoped())->toBeFalse();
    });

    it('flags dated artifacts', function (): void {
        expect(DocumentType::Spec->isDatedArtifact())->toBeTrue()
            ->and(DocumentType::Plan->isDatedArtifact())->toBeTrue()
            ->and(DocumentType::Adr->isDatedArtifact())->toBeFalse();
    });

    it('exposes label, icon and order for every case', function (): void {
        foreach (DocumentType::cases() as $type) {
            expect($type->label())->not->toBeEmpty()
                ->and($type->icon())->not->toBeEmpty()
                ->and($type->order())->toBeGreaterThan(0);
        }
    });
});

describe('AdrStatus', function (): void {
    it('parses inline status with a trailing date', function (): void {
        expect(AdrStatus::fromRaw('Accepted (2026-06-08)'))->toBe(AdrStatus::Accepted)
            ->and(AdrStatus::fromRaw('Superseded'))->toBe(AdrStatus::Superseded);
    });

    it('falls back to proposed for unknown or null values', function (): void {
        expect(AdrStatus::fromRaw('garbage'))->toBe(AdrStatus::Proposed)
            ->and(AdrStatus::fromRaw(null))->toBe(AdrStatus::Proposed);
    });

    it('has a color and label for every case', function (): void {
        foreach (AdrStatus::cases() as $status) {
            expect($status->color())->not->toBeEmpty()
                ->and($status->label())->not->toBeEmpty();
        }
    });
});

describe('PlanStatus', function (): void {
    it('derives from checkbox progress', function (): void {
        expect(PlanStatus::fromProgress(0, 0))->toBe(PlanStatus::Proposed)
            ->and(PlanStatus::fromProgress(0, 5))->toBe(PlanStatus::Proposed)
            ->and(PlanStatus::fromProgress(2, 5))->toBe(PlanStatus::InProgress)
            ->and(PlanStatus::fromProgress(5, 5))->toBe(PlanStatus::Completed)
            ->and(PlanStatus::fromProgress(6, 5))->toBe(PlanStatus::Completed);
    });
});

describe('PlanMetadata', function (): void {
    it('computes the progress percentage', function (): void {
        expect(new PlanMetadata(PlanStatus::InProgress, 2, 5)->progress())->toBe(40)
            ->and(new PlanMetadata(PlanStatus::Proposed, 0, 0)->progress())->toBe(0);
    });
});

describe('DiscoveredDocument', function (): void {
    it('exposes section and dated flag from its type', function (): void {
        $doc = new DiscoveredDocument(
            type: DocumentType::Spec,
            absolutePath: '/x/2026-05-01-foo.md',
            slug: '2026-05-01-foo',
            url: '/docs/specs/2026-05-01-foo',
            title: 'Foo',
            date: CarbonImmutable::parse('2026-05-01'),
        );

        expect($doc->section())->toBe('specs')
            ->and($doc->isDatedArtifact())->toBeTrue();
    });
});

describe('NavigationGroup', function (): void {
    it('is empty without documents nor non-empty subgroups', function (): void {
        expect(new NavigationGroup('Módulos')->isEmpty())->toBeTrue()
            ->and(new NavigationGroup('Decisões', documents: [docsDtoFixture()])->isEmpty())->toBeFalse();
    });

    it('flattens to the sidebar array shape', function (): void {
        $group = new NavigationGroup(
            title: 'Decisões',
            icon: 'scale',
            documents: [docsDtoFixture('/docs/decisions/x', 'X')],
            subgroups: [new NavigationGroup('Moderation', documents: [docsDtoFixture('/docs/decisions/moderation/0001', 'ADR 1')])],
        );

        $array = $group->toArray();

        expect($array['title'])->toBe('Decisões')
            ->and($array['icon'])->toBe('scale')
            ->and($array['pages'])->toBe([['title' => 'X', 'url' => '/docs/decisions/x']])
            ->and($array['subgroups'][0]['pages'][0]['url'])->toBe('/docs/decisions/moderation/0001');
    });
});

describe('DocumentTree', function (): void {
    it('finds by url, tolerating trailing and leading slashes', function (): void {
        $document = docsDtoFixture('/docs/specs/foo', 'Foo');
        $tree = new DocumentTree(groups: [], byUrl: ['/docs/specs/foo' => $document]);

        expect($tree->find('/docs/specs/foo'))->toBe($document)
            ->and($tree->find('docs/specs/foo/'))->toBe($document)
            ->and($tree->find('/docs/missing'))->toBeNull();
    });

    it('omits empty groups from the sidebar', function (): void {
        $tree = new DocumentTree(
            groups: [
                new NavigationGroup('Decisões', 'scale', 2, [docsDtoFixture('/docs/decisions/x', 'X')]),
                new NavigationGroup('Módulos', 'cube', 6),
            ],
            byUrl: [],
        );

        $sidebar = $tree->toSidebar();

        expect($sidebar)->toHaveCount(1)
            ->and($sidebar[0]['title'])->toBe('Decisões');
    });

    it('returns the first document as the landing target', function (): void {
        $first = docsDtoFixture('/docs/glossary/context-map', 'Map');
        $tree = new DocumentTree(
            groups: [new NavigationGroup('Glossário', 'book-open', 1, [$first])],
            byUrl: [],
        );

        expect($tree->first())->toBe($first);
    });
});
