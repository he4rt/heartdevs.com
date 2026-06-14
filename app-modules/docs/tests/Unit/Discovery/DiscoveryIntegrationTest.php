<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\Actions\DiscoverDocumentSourcesAction;
use He4rt\Docs\Discovery\DocumentRegistry;
use He4rt\Docs\Discovery\DTOs\DocumentSource;
use Illuminate\Support\Facades\Cache;

describe('DiscoverDocumentSourcesAction (real repository)', function (): void {
    it('finds module decisions and contexts while skipping vendor and fixtures', function (): void {
        $paths = array_map(
            static fn (DocumentSource $source): string => str_replace('\\', '/', (string) $source->file->getRealPath()),
            resolve(DiscoverDocumentSourcesAction::class)->execute(),
        );

        expect($paths)->not->toBeEmpty()
            ->and(collect($paths)->contains(fn (string $p): bool => str_contains($p, 'app-modules/moderation/docs/adr/')))->toBeTrue()
            ->and(collect($paths)->contains(fn (string $p): bool => str_ends_with($p, 'app-modules/moderation/CONTEXT.md')))->toBeTrue()
            ->and(collect($paths)->every(fn (string $p): bool => !str_contains($p, '/vendor/') && !str_contains($p, '/tests/fixtures/')))->toBeTrue();
    });
});

describe('DocumentRegistry caching', function (): void {
    beforeEach(function (): void {
        config()->set('cache.default', 'array');
        config()->set('docs.cache.enabled', true);
        config()->set('docs.cache.ttl', 60);
        Cache::flush();
    });

    it('caches the navigation tree', function (): void {
        $registry = resolve(DocumentRegistry::class);

        $tree = $registry->tree();

        expect(Cache::has('docs.tree'))->toBeTrue()
            ->and($tree->first())->not->toBeNull();
    });

    it('renders a discovered document to non-empty html', function (): void {
        $registry = resolve(DocumentRegistry::class);
        $document = $registry->tree()->first();

        expect($document)->not->toBeNull();

        $rendered = $registry->render($document);

        expect($rendered->html)->not->toBeEmpty();
    });
});
