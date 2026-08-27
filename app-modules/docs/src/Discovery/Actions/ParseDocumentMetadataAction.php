<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Actions;

use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

/**
 * Extracts front-matter and a resolved title from raw markdown, without
 * rendering HTML. This is the cheap step used during discovery.
 */
final readonly class ParseDocumentMetadataAction
{
    public function execute(string $content, ?string $filename = null): DocumentMetadata
    {
        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
        $result = $parser->parse($content);

        $raw = $result->getFrontMatter();
        /** @var array<string, mixed> $frontMatter */
        $frontMatter = is_array($raw) ? $raw : [];

        $body = $result->getContent();

        return new DocumentMetadata(
            frontMatter: $frontMatter,
            title: $this->resolveTitle($frontMatter, $body, $filename),
            body: $body,
        );
    }

    /**
     * Title resolution order: front-matter `title` → first `# H1` → humanized
     * filename (ISO date prefix stripped) → "Untitled".
     *
     * @param  array<string, mixed>  $frontMatter
     */
    private function resolveTitle(array $frontMatter, string $body, ?string $filename): string
    {
        $fromFrontMatter = $frontMatter['title'] ?? null;

        if (is_string($fromFrontMatter) && mb_trim($fromFrontMatter) !== '') {
            return mb_trim($fromFrontMatter);
        }

        if (preg_match('/^#\s+(.+?)\s*$/m', $body, $matches) === 1) {
            return mb_trim($matches[1]);
        }

        if ($filename !== null) {
            $name = (string) preg_replace('/\.md$/i', '', basename($filename));
            $name = (string) preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $name);
            $headline = Str::headline($name);

            if ($headline !== '') {
                return $headline;
            }
        }

        return 'Untitled';
    }
}
