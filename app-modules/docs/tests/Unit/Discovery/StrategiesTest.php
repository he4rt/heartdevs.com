<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\Actions\ParseDocumentMetadataAction;
use He4rt\Docs\Discovery\DTOs\AdrMetadata;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\DTOs\PlanMetadata;
use He4rt\Docs\Discovery\Enums\AdrStatus;
use He4rt\Docs\Discovery\Enums\DocumentType;
use He4rt\Docs\Discovery\Enums\PlanStatus;
use He4rt\Docs\Discovery\Strategies\AdrStrategy;
use He4rt\Docs\Discovery\Strategies\ContextMapStrategy;
use He4rt\Docs\Discovery\Strategies\ContextStrategy;
use He4rt\Docs\Discovery\Strategies\GuideStrategy;
use He4rt\Docs\Discovery\Strategies\PlanStrategy;
use He4rt\Docs\Discovery\Strategies\PrdStrategy;
use He4rt\Docs\Discovery\Strategies\ReadmeStrategy;
use He4rt\Docs\Discovery\Strategies\SpecStrategy;

function strategyParser(): ParseDocumentMetadataAction
{
    return new ParseDocumentMetadataAction();
}

function fixtureFile(string $relative): SplFileInfo
{
    return new SplFileInfo(__DIR__.'/../../fixtures/'.$relative);
}

function fakeFile(string $path): SplFileInfo
{
    return new SplFileInfo($path);
}

describe('matches', static function (): void {
    it('routes each path to the right strategy', function (): void {
        expect(new AdrStrategy(strategyParser())->matches(fakeFile('/r/app-modules/moderation/docs/adr/0001-x.md')))->toBeTrue()
            ->and(new ContextStrategy(strategyParser())->matches(fakeFile('/r/app-modules/moderation/CONTEXT.md')))->toBeTrue()
            ->and(new ContextMapStrategy(strategyParser())->matches(fakeFile('/r/CONTEXT-MAP.md')))->toBeTrue()
            ->and(new SpecStrategy(strategyParser())->matches(fakeFile('/r/docs/specs/2026-x-design.md')))->toBeTrue()
            ->and(new SpecStrategy(strategyParser())->matches(fakeFile('/r/docs/superpowers/specs/2026-x.md')))->toBeTrue()
            ->and(new PlanStrategy(strategyParser())->matches(fakeFile('/r/docs/plans/2026-x.md')))->toBeTrue()
            ->and(new PrdStrategy(strategyParser())->matches(fakeFile('/r/docs/prd/x.md')))->toBeTrue()
            ->and(new ReadmeStrategy(strategyParser())->matches(fakeFile('/r/app-modules/identity/README.md')))->toBeTrue()
            ->and(new GuideStrategy(strategyParser())->matches(fakeFile('/r/resources/docs/3.x/installation.md')))->toBeTrue();
    });

    it('does not match unrelated files', function (): void {
        expect(new AdrStrategy(strategyParser())->matches(fakeFile('/r/docs/specs/x.md')))->toBeFalse()
            ->and(new ReadmeStrategy(strategyParser())->matches(fakeFile('/r/app-modules/x/CONTEXT.md')))->toBeFalse()
            ->and(new GuideStrategy(strategyParser())->matches(fakeFile('/r/resources/docs/3.x/documentation.md')))->toBeFalse()
            ->and(new ContextStrategy(strategyParser())->matches(fakeFile('/r/CONTEXT-MAP.md')))->toBeFalse();
    });
});

describe('AdrStrategy parse', static function (): void {
    it('parses inline status, date, deciders and strips the title prefix', function (): void {
        $doc = new AdrStrategy(strategyParser())->parse(fixtureFile('modules/sample/docs/adr/0001-sample-decision.md'), 'sample');

        expect($doc->type)->toBe(DocumentType::Adr)
            ->and($doc->title)->toBe('Sample Hybrid Pipeline')
            ->and($doc->slug)->toBe('0001-sample-decision')
            ->and($doc->url)->toBe('/docs/decisions/sample/0001-sample-decision')
            ->and($doc->moduleName)->toBe('sample')
            ->and($doc->order)->toBe(1)
            ->and($doc->date?->format('Y-m-d'))->toBe('2026-05-16')
            ->and($doc->metadata)->toBeInstanceOf(AdrMetadata::class);

        /** @var AdrMetadata $metadata */
        $metadata = $doc->metadata;
        expect($metadata->status)->toBe(AdrStatus::Accepted)
            ->and($metadata->deciders)->toBe(['danielhe4rt', 'clinton']);
    });

    it('reads status, deciders and relations from front-matter', function (): void {
        $doc = new AdrStrategy(strategyParser())->parse(fixtureFile('modules/sample/docs/adr/0002-frontmatter-decision.md'), 'sample');

        /** @var AdrMetadata $metadata */
        $metadata = $doc->metadata;

        expect($doc->title)->toBe('Front Matter Decision')
            ->and($metadata->status)->toBe(AdrStatus::Superseded)
            ->and($metadata->deciders)->toBe(['alice'])
            ->and($metadata->relations)->toContain(['label' => 'Superseded_by', 'target' => 'sample/0003-newer-decision']);
    });
});

describe('PlanStrategy parse', static function (): void {
    it('derives status from task checkboxes', function (): void {
        $doc = new PlanStrategy(strategyParser())->parse(fixtureFile('modules/sample/docs/plans/2026-05-02-sample.md'), 'sample');

        expect($doc->type)->toBe(DocumentType::Plan)
            ->and($doc->url)->toBe('/docs/plans/sample/2026-05-02-sample')
            ->and($doc->date?->format('Y-m-d'))->toBe('2026-05-02')
            ->and($doc->metadata)->toBeInstanceOf(PlanMetadata::class);

        /** @var PlanMetadata $metadata */
        $metadata = $doc->metadata;
        expect($metadata->status)->toBe(PlanStatus::InProgress)
            ->and($metadata->completedSteps)->toBe(2)
            ->and($metadata->totalSteps)->toBe(3);
    });
});

describe('ReadmeStrategy parse', static function (): void {
    it('builds the module url from the module name', function (): void {
        $doc = new ReadmeStrategy(strategyParser())->parse(fixtureFile('modules/sample/README.md'), 'sample');

        expect($doc->type)->toBe(DocumentType::Module)
            ->and($doc->title)->toBe('Sample Module')
            ->and($doc->url)->toBe('/docs/modules/sample');
    });
});

describe('GuideStrategy parse', static function (): void {
    it('reads order from front-matter and computes reading time', function (): void {
        $doc = new GuideStrategy(strategyParser())->parse(fixtureFile('resources/docs/3.x/guide-order.md'), null);

        expect($doc->title)->toBe('Guide With Order')
            ->and($doc->order)->toBe(4)
            ->and($doc->readingMinutes)->toBe(1);
    });
});

describe('DocumentMetadata int', static function (): void {
    it('reads integers and numeric strings, null otherwise', function (): void {
        $meta = new DocumentMetadata(
            frontMatter: ['order' => 3, 'numeric' => '7', 'text' => 'abc'],
            title: 'T',
            body: 'b',
        );

        expect($meta->int('order'))->toBe(3)
            ->and($meta->int('numeric'))->toBe(7)
            ->and($meta->int('text'))->toBeNull()
            ->and($meta->int('missing'))->toBeNull();
    });
});
