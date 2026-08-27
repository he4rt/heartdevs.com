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
use He4rt\Docs\Discovery\Strategies\IntroductionStrategy;
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
            new IntroductionStrategy($parser),
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
        sourceFixture('CONTEXT-MAP.md', module: null),
        sourceFixture('modules/sample/CONTEXT.md', 'sample'),
        sourceFixture('modules/sample/docs/adr/0001-sample-decision.md', 'sample'),
        sourceFixture('modules/sample/docs/adr/0002-frontmatter-decision.md', 'sample'),
        sourceFixture('modules/sample/docs/plans/2026-05-02-sample.md', 'sample'),
        sourceFixture('modules/sample/README.md', 'sample'),
        sourceFixture('modules/sample/docs/specs/2026-01-01-hidden-spec.md', 'sample'),
    ]);
}

it('groups documents into tiers in order, omitting empty tiers', function (): void {
    $titles = array_map(static fn (array $group): string => $group['title'], sampleTree()->toSidebar());

    // No Introduction-tier docs in the fixtures, so that tier is omitted.
    expect($titles)->toBe(['Getting Started', 'Engenharia']);
});

it('places the context map (module-less glossary) directly under getting started', function (): void {
    $gettingStarted = sampleTree()->toSidebar()[0];

    expect($gettingStarted['title'])->toBe('Getting Started')
        ->and($gettingStarted['subgroups'])->toBeEmpty()
        ->and($gettingStarted['pages'][0]['url'])->toBe('/docs/glossary/context-map');
});

it('sub-groups engineering docs by module alphabetically', function (): void {
    $engineering = sampleTree()->toSidebar()[1];

    expect($engineering['title'])->toBe('Engenharia')
        ->and($engineering['pages'])->toBeEmpty()
        ->and($engineering['subgroups'][0]['title'])->toBe('Sample');
});

it('orders each module by reading order: module, glossary, decisions, plan', function (): void {
    $module = sampleTree()->toSidebar()[1]['subgroups'][0];

    $urls = array_map(static fn (array $page): string => $page['url'], $module['pages']);

    expect($urls)->toBe([
        '/docs/modules/sample',
        '/docs/glossary/sample',
        '/docs/decisions/sample/0001-sample-decision',
        '/docs/decisions/sample/0002-frontmatter-decision',
        '/docs/plans/sample/2026-05-02-sample',
    ]);
});

function multiModuleTree(): DocumentTree
{
    return buildTreeAction()->execute([
        sourceFixture('CONTEXT-MAP.md', module: null),
        sourceFixture('docs/specs/2026-03-01-transversal.md', module: null),
        sourceFixture('modules/sample/README.md', 'sample'),
        sourceFixture('modules/alpha/README.md', 'alpha'),
    ]);
}

it('orders engineering subgroups alphabetically by module', function (): void {
    $engineering = multiModuleTree()->toSidebar()[1];

    $modules = array_map(static fn (array $group): string => $group['title'], $engineering['subgroups']);

    expect($engineering['title'])->toBe('Engenharia')
        ->and($modules)->toBe(['Alpha', 'Sample']);
});

it('renders module-less engineering docs directly above the subgroups', function (): void {
    $engineering = multiModuleTree()->toSidebar()[1];

    expect($engineering['pages'][0]['url'])->toBe('/docs/specs/2026-03-01-transversal')
        ->and($engineering['subgroups'][0]['title'])->toBe('Alpha');
});

it('keeps the curated tier before engineering even with many modules', function (): void {
    $titles = array_map(static fn (array $group): string => $group['title'], multiModuleTree()->toSidebar());

    expect($titles)->toBe(['Getting Started', 'Engenharia']);
});

it('omits a tier whose only documents are hidden', function (): void {
    $tree = buildTreeAction()->execute([
        sourceFixture('CONTEXT-MAP.md', module: null),
        sourceFixture('modules/sample/docs/specs/2026-01-01-hidden-spec.md', 'sample'),
    ]);

    $titles = array_map(static fn (array $group): string => $group['title'], $tree->toSidebar());

    // The hidden spec is the only Engineering candidate, so that tier is gone.
    expect($titles)->toBe(['Getting Started']);
});

it('excludes hidden documents from the lookup index', function (): void {
    expect(sampleTree()->find('/docs/specs/sample/2026-01-01-hidden-spec'))->toBeNull();
});

it('resolves documents by url', function (): void {
    expect(sampleTree()->find('/docs/modules/sample')?->title)->toBe('Sample Module');
});
