<?php

declare(strict_types=1);

namespace He4rt\Documentation\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class DocumentationHelper
{
    /**
     * Get all documentation pages for a specific version
     */
    public static function getVersionPages(string $version): array
    {
        $docsPath = base_path('docs/' . $version);

        if (! File::isDirectory($docsPath)) {
            return [];
        }

        $files = File::files($docsPath);
        $pages = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $slug = $file->getFilenameWithoutExtension();
            $content = File::get($file->getPathname());
            $frontMatter = YamlFrontMatter::parse($content);

            $pages[] = [
                'slug' => $slug,
                'title' => $frontMatter->matter('title', Str::title(str_replace('-', ' ', $slug))),
                'order' => $frontMatter->matter('order', 999),
                'group' => $frontMatter->matter('group', 'Geral'),
                'icon' => $frontMatter->matter('icon', 'document-text'),
            ];
        }

        // Sort by order
        usort($pages, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $pages;
    }

    /**
     * Group pages by their group property
     */
    public static function groupPages(array $pages): array
    {
        $grouped = [];

        foreach ($pages as $page) {
            $group = $page['group'];

            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][] = $page;
        }

        return $grouped;
    }

    /**
     * Extract headings from markdown content for table of contents
     */
    public static function extractHeadings(string $markdown): array
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

    /**
     * Get available documentation versions
     */
    public static function getAvailableVersions(): array
    {
        $docsPath = base_path('docs');

        if (! File::isDirectory($docsPath)) {
            return [];
        }

        $directories = File::directories($docsPath);
        $versions = [];

        foreach ($directories as $directory) {
            $version = basename((string) $directory);

            // Skip if it's not a version directory (contains 'x' or is numeric)
            if (! str_contains($version, '.') && ! is_numeric($version)) {
                continue;
            }

            $versions[] = [
                'slug' => $version,
                'label' => $version === 'master' ? 'Master' : 'v' . $version,
            ];
        }

        return $versions;
    }
}
