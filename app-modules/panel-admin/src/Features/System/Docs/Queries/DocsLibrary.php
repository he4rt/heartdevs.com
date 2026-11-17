<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Features\System\Docs\Queries;

use He4rt\PanelAdmin\Features\System\Docs\Data\DocNodeData;
use He4rt\PanelAdmin\Features\System\Docs\Data\DocPageData;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Reads the admin docs from docs/admin/{locale}: a file is a page, and a file with `type: group`
 * lists the pages in the folder of the same name. A page missing in the locale falls back to English.
 */
final class DocsLibrary
{
    private const int DEFAULT_ORDER = 999;

    private ?MarkdownConverter $converter = null;

    /** @return array<int, DocNodeData> */
    public function tree(string $locale): array
    {
        return $this->nodes('', $locale);
    }

    public function firstPath(string $locale): ?string
    {
        return $this->tree($locale)[0]->path ?? null;
    }

    public function page(string $path, string $locale): ?DocPageData
    {
        foreach (collect([$locale, $this->fallbackLocale()])->unique() as $candidate) {
            $file = $this->file($path, $candidate);

            if ($file === null) {
                continue;
            }

            $document = $this->render((string) file_get_contents($file));

            return new DocPageData(
                path: $path,
                title: $document['title'] ?? $path,
                html: $document['html'],
                locale: $candidate,
                isFallback: $candidate !== $locale,
                children: $document['isGroup'] ? $this->nodes($path, $locale) : [],
            );
        }

        return null;
    }

    /** @return array<int, DocNodeData> */
    private function nodes(string $folder, string $locale): array
    {
        $directory = $this->directory($folder, $this->fallbackLocale());

        if (!is_dir($directory)) {
            return [];
        }

        return collect(Finder::create()->files()->name('*.md')->depth(0)->in($directory))
            ->map(function (SplFileInfo $file) use ($folder, $locale): array {
                $path = mb_ltrim($folder.'/'.$file->getBasename('.md'), '/');
                $matter = $this->frontMatter($this->file($path, $locale) ?? $file->getPathname());

                return [
                    'order' => $matter['order'],
                    'node' => new DocNodeData(
                        path: $path,
                        title: $matter['title'] ?? $path,
                        url: route('panel-admin::docs.show', ['path' => $path], absolute: false),
                        children: $matter['isGroup'] ? $this->nodes($path, $locale) : [],
                    ),
                ];
            })
            ->sort(fn (array $left, array $right): int => [$left['order'], $left['node']->title] <=> [$right['order'], $right['node']->title])
            ->map(fn (array $entry): DocNodeData => $entry['node'])
            ->values()
            ->all();
    }

    /**
     * The route allows only [a-z0-9-] segments, and the real path must stay inside the docs folder.
     */
    private function file(string $path, string $locale): ?string
    {
        $root = realpath($this->directory('', $locale));
        $file = realpath($this->directory('', $locale).'/'.$path.'.md');
        $isInsideDocs = $root !== false && $file !== false && str_starts_with($file, $root.DIRECTORY_SEPARATOR);

        return $isInsideDocs ? $file : null;
    }

    private function directory(string $folder, string $locale): string
    {
        return mb_rtrim(sprintf('%s/%s/%s', config()->string('panel-admin.docs.path'), $locale, $folder), '/');
    }

    private function fallbackLocale(): string
    {
        return config()->string('panel-admin.docs.fallback_locale');
    }

    /** @return array{title: string|null, order: int, isGroup: bool} */
    private function frontMatter(string $file): array
    {
        $document = $this->render((string) file_get_contents($file));

        return ['title' => $document['title'], 'order' => $document['order'], 'isGroup' => $document['isGroup']];
    }

    /** @return array{title: string|null, order: int, isGroup: bool, html: string} */
    private function render(string $markdown): array
    {
        $result = $this->converter()->convert($markdown);
        $matter = $result instanceof RenderedContentWithFrontMatter ? $result->getFrontMatter() : [];
        $matter = is_array($matter) ? $matter : [];

        return [
            'title' => is_string($matter['title'] ?? null) ? $matter['title'] : null,
            'order' => is_int($matter['order'] ?? null) ? $matter['order'] : self::DEFAULT_ORDER,
            'isGroup' => ($matter['type'] ?? null) === 'group',
            'html' => $result->getContent(),
        ];
    }

    /**
     * The docs live in the repository, but raw HTML is still stripped from them.
     */
    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
