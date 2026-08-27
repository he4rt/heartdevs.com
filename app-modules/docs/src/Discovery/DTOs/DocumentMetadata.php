<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\DTOs;

/**
 * The lightweight result of parsing a markdown file: its front-matter, the
 * resolved title, and the body (front-matter stripped). No HTML is rendered.
 */
final readonly class DocumentMetadata
{
    /**
     * @param  array<string, mixed>  $frontMatter
     */
    public function __construct(
        public array $frontMatter,
        public string $title,
        public string $body,
    ) {}

    /**
     * Read a string value from the front-matter, or null.
     */
    public function string(string $key): ?string
    {
        $value = $this->frontMatter[$key] ?? null;

        return is_string($value) && mb_trim($value) !== '' ? mb_trim($value) : null;
    }

    /**
     * Read an integer value from the front-matter (accepts int or numeric string), or null.
     */
    public function int(string $key): ?int
    {
        $value = $this->frontMatter[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric(mb_trim($value))) {
            return (int) mb_trim($value);
        }

        return null;
    }

    /**
     * Read a list of strings from the front-matter (accepts a scalar or a list).
     *
     * @return list<string>
     */
    public function list(string $key): array
    {
        $value = $this->frontMatter[$key] ?? null;

        if (is_string($value) && mb_trim($value) !== '') {
            return [mb_trim($value)];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_string($item) ? mb_trim($item) : '',
            $value,
        ), static fn (string $item): bool => $item !== ''));
    }
}
