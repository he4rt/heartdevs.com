<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\Actions\ParseDocumentMetadataAction;
use He4rt\Docs\Discovery\Contracts\DocumentTypeStrategyContract;
use He4rt\Docs\Discovery\DTOs\AdrMetadata;
use He4rt\Docs\Discovery\DTOs\DiscoveredDocument;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\DTOs\PlanMetadata;
use Illuminate\Support\Str;
use SplFileInfo;
use Throwable;

/**
 * Shared Template Method base for document strategies: it reads the file,
 * parses metadata, and assembles a DiscoveredDocument via small overridable
 * hooks. Concrete strategies declare their type/matching and refine the hooks.
 */
abstract readonly class AbstractDocumentStrategy implements DocumentTypeStrategyContract
{
    public function __construct(
        protected ParseDocumentMetadataAction $parser,
    ) {}

    public function parse(SplFileInfo $file, ?string $moduleName): DiscoveredDocument
    {
        $path = $this->realPath($file);
        $content = (string) file_get_contents($path);
        $meta = $this->parser->execute($content, $file->getFilename());

        $module = $this->moduleName($moduleName, $meta);
        $slug = $this->slug($file, $meta, $module);

        return new DiscoveredDocument(
            type: $this->type(),
            absolutePath: $path,
            slug: $slug,
            url: $this->url($module, $slug, $meta),
            title: $this->title($file, $meta),
            moduleName: $module,
            date: $this->date($file, $meta),
            order: $this->order($file, $meta),
            hidden: ($meta->frontMatter['hidden'] ?? false) === true,
            author: $meta->string('author'),
            metadata: $this->metadata($content, $meta),
        );
    }

    protected function slug(SplFileInfo $file, DocumentMetadata $meta, ?string $module): string
    {
        return Str::of($file->getFilename())->beforeLast('.md')->slug()->value();
    }

    protected function url(?string $module, string $slug, DocumentMetadata $meta): string
    {
        $segments = array_filter(['docs', $this->type()->value, $module, $slug], static fn (?string $s): bool => $s !== null && $s !== '');

        return '/'.implode('/', $segments);
    }

    protected function title(SplFileInfo $file, DocumentMetadata $meta): string
    {
        return $meta->title;
    }

    protected function moduleName(?string $module, DocumentMetadata $meta): ?string
    {
        return $meta->string('module') ?? $module;
    }

    protected function date(SplFileInfo $file, DocumentMetadata $meta): ?CarbonImmutable
    {
        $fromFrontMatter = $meta->string('date');

        if ($fromFrontMatter !== null) {
            $parsed = $this->tryDate($fromFrontMatter);

            if ($parsed instanceof CarbonImmutable) {
                return $parsed;
            }
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $file->getFilename(), $matches) === 1) {
            return $this->tryDate($matches[1]);
        }

        return null;
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        return 0;
    }

    protected function metadata(string $content, DocumentMetadata $meta): AdrMetadata|PlanMetadata|null
    {
        return null;
    }

    protected function path(SplFileInfo $file): string
    {
        return str_replace('\\', '/', $file->getPathname());
    }

    private function realPath(SplFileInfo $file): string
    {
        $real = $file->getRealPath();

        return $real !== false ? $real : $file->getPathname();
    }

    private function tryDate(string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
