<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery;

use He4rt\Docs\Discovery\Actions\BuildDocumentTreeAction;
use He4rt\Docs\Discovery\Actions\RenderMarkdownAction;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\DTOs\DocumentTree;
use He4rt\Docs\Discovery\DTOs\RenderedMarkdown;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;

/**
 * Read facade over the discovery pipeline. Caches the navigation tree and the
 * rendered HTML per page (keyed by file mtime), bypassing the cache locally.
 */
final readonly class DocumentRegistry
{
    private const string TREE_KEY = 'docs.tree';

    public function __construct(
        private BuildDocumentTreeAction $build,
        private RenderMarkdownAction $renderer,
        private Cache $cache,
        private Config $config,
        private Application $app,
    ) {}

    public function tree(): DocumentTree
    {
        if (!$this->shouldCache()) {
            return $this->build->execute();
        }

        return $this->cache->remember(self::TREE_KEY, $this->ttl(), fn (): DocumentTree => $this->build->execute());
    }

    public function find(string $url): ?DiscoveredDocument
    {
        return $this->tree()->find($url);
    }

    public function render(DiscoveredDocument $document): RenderedMarkdown
    {
        $resolver = fn (): RenderedMarkdown => $this->renderer->execute($this->contents($document));

        if (!$this->shouldCache()) {
            return $resolver();
        }

        return $this->cache->remember($this->renderKey($document), $this->ttl(), $resolver);
    }

    /**
     * Warm the navigation tree and every page's rendered HTML.
     */
    public function warm(): int
    {
        $this->forget();

        $tree = $this->tree();

        foreach ($tree->byUrl as $document) {
            $this->render($document);
        }

        return count($tree->byUrl);
    }

    public function forget(): void
    {
        $this->cache->forget(self::TREE_KEY);
    }

    private function shouldCache(): bool
    {
        return (bool) $this->config->get('docs.cache.enabled', default: true) && !$this->app->environment('local');
    }

    private function ttl(): int
    {
        return (int) $this->config->get('docs.cache.ttl', 3_600);
    }

    private function renderKey(DiscoveredDocument $document): string
    {
        $mtime = is_file($document->absolutePath) ? (int) filemtime($document->absolutePath) : 0;

        return 'docs.render.'.md5($document->absolutePath).'.'.$mtime;
    }

    private function contents(DiscoveredDocument $document): string
    {
        return is_file($document->absolutePath) ? (string) file_get_contents($document->absolutePath) : '';
    }
}
