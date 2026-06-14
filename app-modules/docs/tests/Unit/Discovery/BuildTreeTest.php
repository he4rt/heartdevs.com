<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\Actions\BuildDocumentTreeAction;
use He4rt\Docs\Discovery\Actions\DiscoverDocumentSourcesAction;
use He4rt\Docs\Discovery\Actions\ParseDocumentMetadataAction;
use He4rt\Docs\Discovery\DTOs\DocumentSource;
use He4rt\Docs\Discovery\DTOs\DocumentTree;
use He4rt\Docs\Discovery\Strategies\AdrStrategy;
use He4rt\Docs\Discovery\Strategies\ContextMapStrategy;
use He4rt\Docs\Discovery\Strategies\ContextStrategy;
use He4rt\Docs\Discovery\Strategies\GuideStrategy;
use He4rt\Docs\Discovery\Strategies\PlanStrategy;
use He4rt\Docs\Discovery\Strategies\PrdStrategy;
use He4rt\Docs\Discovery\Strategies\ReadmeStrategy;
use He4rt\Docs\Discovery\Strategies\SpecStrategy;

function buildTreeAction(): BuildDocumentTreeAction
{
    $parser = new ParseDocumentMetadataAction();

    return new BuildDocumentTreeAction(
        resolve(DiscoverDocumentSourcesAction::class),
        [
            new ContextMapStrategy($parser),
            new ContextStrategy($parser),
            new AdrStrategy($parser),
            new SpecStrategy($parser),
            new PlanStrategy($parser),
            new PrdStrategy($parser),
            new ReadmeStrategy($parser),
            new GuideStrategy($parser),
        ],
    );
}

function sourceFixture(string $relative, ?string $module): DocumentSource
{
    return new DocumentSource(new SplFileInfo(__DIR__.'/../../fixtures/'.$relative), $module);
}

function sampleTree(): DocumentTree
{
    return buildTreeAction()->execute([
        sourceFixture('CONTEXT-MAP.md', null),
        sourceFixture('modules/sample/CONTEXT.md', 'sample'),
        sourceFixture('modules/sample/docs/adr/0001-sample-decision.md', 'sample'),
        sourceFixture('modules/sample/docs/adr/0002-frontmatter-decision.md', 'sample'),
        sourceFixture('modules/sample/docs/plans/2026-05-02-sample.md', 'sample'),
        sourceFixture('modules/sample/README.md', 'sample'),
        sourceFixture('modules/sample/docs/specs/2026-01-01-hidden-spec.md', 'sample'),
    ]);
}

it('groups types in order and omits the hidden-only spec group', function (): void {
    $titles = array_map(static fn (array $group): string => $group['title'], sampleTree()->toSidebar());

    expect($titles)->toBe(['Glossário', 'Decisões', 'Plans', 'Módulos']);
});

it('lists the glossary flat with the context map first', function (): void {
    $glossary = sampleTree()->toSidebar()[0];

    expect($glossary['subgroups'])->toBeEmpty()
        ->and($glossary['pages'][0]['url'])->toBe('/docs/glossary/context-map')
        ->and($glossary['pages'][1]['url'])->toBe('/docs/glossary/sample');
});

it('sub-groups decisions by module ordered by adr number', function (): void {
    $decisions = sampleTree()->toSidebar()[1];

    expect($decisions['pages'])->toBeEmpty()
        ->and($decisions['subgroups'][0]['title'])->toBe('Sample')
        ->and($decisions['subgroups'][0]['pages'][0]['url'])->toBe('/docs/decisions/sample/0001-sample-decision')
        ->and($decisions['subgroups'][0]['pages'][1]['url'])->toBe('/docs/decisions/sample/0002-frontmatter-decision');
});

it('excludes hidden documents from the lookup index', function (): void {
    expect(sampleTree()->find('/docs/specs/sample/2026-01-01-hidden-spec'))->toBeNull();
});

it('resolves documents by url', function (): void {
    expect(sampleTree()->find('/docs/modules/sample')?->title)->toBe('Sample Module');
});
