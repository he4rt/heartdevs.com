<?php

declare(strict_types=1);

namespace He4rt\Docs;

use Carbon\CarbonInterval;
use He4rt\Docs\CommonMark\Markdown\GithubFlavoredMarkdownConverter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\CommonMark\Output\RenderedContent;

class Documentation
{
    public function __construct(
        protected Filesystem $files,
        protected Cache $cache
    ) {}

    /**
     * Replace the version place-holder in links.
     *
     * @param  string  $version
     * @param  string  $content
     */
    public static function replaceLinks($version, RenderedContent|string $content): string
    {
        $content = $content instanceof RenderedContent ? $content->getContent() : $content;

        return str_replace('%7B%7Bversion%7D%7D', $version, $content);
    }

    /**
     * Get the publicly available versions of the documentation
     *
     * @return array<string, string>
     */
    public static function getDocVersions(): array
    {
        return [
            '3.x' => '3.x',
        ];
    }

    /**
     * Get the documentation index page.
     *
     * @return string|null
     */
    public function getIndex(string $version)
    {
        return $this->cache->remember('docs.'.$version.'.index', 5, function () use ($version): ?string {
            $path = base_path('resources/docs/'.$version.'/documentation.md');

            if ($this->files->exists($path)) {
                return static::replaceLinks(
                    $version,
                    new GithubFlavoredMarkdownConverter()->convert($this->files->get($path))
                );
            }

            return null;
        });
    }

    /**
     * Get the given documentation page.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $version, string $page): ?array
    {
        return $this->cache->remember('docs.'.$version.'.'.$page, 5, function () use ($version, $page): ?array {
            $path = base_path('resources/docs/'.$version.'/'.$page.'.md');

            if ($this->files->exists($path)) {
                $content = $this->files->get($path);

                $htmlContent = new GithubFlavoredMarkdownConverter()->convert($content);

                return [
                    'content' => static::replaceLinks($version, $htmlContent),
                    'toc' => $this->getToc($content),
                ];
            }

            return null;
        });
    }

    /**
     * Get the array based index representation of the documentation.
     *
     * @return array<string, mixed>
     */
    public function indexArray(string $version): array
    {
        return $this->cache->remember('docs.{'.$version.'}.index', CarbonInterval::second(1), function () use ($version): array {
            $path = base_path('resources/docs/'.$version.'/documentation.md');

            if (!$this->files->exists($path)) {
                return [];
            }

            return [
                'pages' => collect(explode(PHP_EOL, static::replaceLinks($version, $this->files->get($path))))
                    ->filter(fn ($line) => Str::contains($line, '/docs/{{version}}/'))
                    ->map(fn ($line) => resource_path(Str::of($line)->afterLast('(/')->before(')')->replace('{{version}}', $version)->append('.md')))
                    ->filter(fn ($path) => $this->files->exists($path))
                    ->mapWithKeys(function ($path): array {
                        $contents = $this->files->get($path);

                        preg_match('/\# (?<title>[^\\n]+)/', $contents, $page);
                        preg_match_all('/<a name="(?<fragments>[^"]+)"><\\/a>\n#+ (?<titles>[^\\n]+)/', $contents, $section);

                        return [
                            (string) Str::of($path)->afterLast('/')->before('.md') => [
                                'title' => $page['title'],
                                'sections' => collect($section['fragments'])
                                    ->combine($section['titles'])
                                    ->map(fn ($title) => ['title' => $title]),
                            ],
                        ];
                    }),
            ];
        });
    }

    /**
     * Check if the given section exists.
     */
    public function sectionExists(string $version, ?string $page): bool
    {
        return $this->files->exists(
            base_path('resources/docs/'.$version.'/'.$page.'.md')
        );
    }

    /**
     * Determine which versions a page exists in.
     *
     * @return Collection<string, string>
     */
    public function versionsContainingPage(string $page): Collection
    {
        return collect(static::getDocVersions())
            ->filter(fn (string $version) => $this->sectionExists($version, $page));
    }

    /**
     * Get the sidebar documentation index.
     *
     * @return array<string, mixed>
     */
    public function getPages(string $version): array
    {
        return $this->cache->remember('docs.'.$version.'.sidebar', 5, function () use ($version): array {
            $path = base_path('resources/docs/'.$version.'/documentation.md');

            if (!$this->files->exists($path)) {
                return [];
            }

            $content = $this->files->get($path);
            $lines = explode(PHP_EOL, $content);
            $sidebar = [];
            $currentCategory = null;

            foreach ($lines as $line) {
                $line = mb_trim($line);

                if ($line === '' || $line === '0') {
                    continue;
                }

                // Check for category header: - ## Category Name
                if (preg_match('/^- ## (.+)$/', $line, $matches)) {
                    $currentCategory = str($matches[1])->slug()->toString();
                    $sidebar[$currentCategory] = [
                        'title' => $matches[1],
                        'pages' => [],
                    ];

                    continue;
                }

                // Check for page link: - [Page Title](url)
                if (preg_match('/^- \[(.+)\]\((.+)\)$/', $line, $matches)) {
                    $title = $matches[1];
                    $url = $matches[2];

                    // Replace {{version}} placeholder
                    $url = str_replace('{{version}}', $version, $url);

                    $page = [
                        'title' => $title,
                        'url' => $url,
                    ];

                    $sidebar[$currentCategory]['pages'][] = $page;
                }
            }

            return $sidebar;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getToc(string $markdown): array
    {
        $headings = [];
        $lines = explode("\n", $markdown);

        foreach ($lines as $line) {
            // Match markdown headings (## or ###)
            if (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
                $level = mb_strlen($matches[1]);
                $text = mb_trim($matches[2]);

                // Remove markdown links from heading text
                $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

                // Generate anchor ID (similar to how markdown renderers do it)
                $id = Str::slug($text);

                $headings[] = [
                    'level' => $level,
                    'text' => $text,
                    'id' => $id,
                ];
            }
        }

        return $headings;
    }
}
