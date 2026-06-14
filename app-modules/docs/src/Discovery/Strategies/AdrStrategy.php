<?php

declare(strict_types=1);

namespace He4rt\Docs\Discovery\Strategies;

use Carbon\CarbonImmutable;
use He4rt\Docs\Discovery\DTOs\AdrMetadata;
use He4rt\Docs\Discovery\DTOs\DocumentMetadata;
use He4rt\Docs\Discovery\Enums\AdrStatus;
use He4rt\Docs\Discovery\Enums\DocumentType;
use SplFileInfo;
use Throwable;

/**
 * Architecture Decision Records: files in any `docs/adr` directory.
 */
final readonly class AdrStrategy extends AbstractDocumentStrategy
{
    public function type(): DocumentType
    {
        return DocumentType::Adr;
    }

    public function matches(SplFileInfo $file): bool
    {
        return str_contains($this->path($file), '/docs/adr/')
            && str_ends_with($file->getFilename(), '.md');
    }

    protected function title(SplFileInfo $file, DocumentMetadata $meta): string
    {
        return (string) preg_replace('/^ADR[-\s]*\d+\s*[:\-—]\s*/i', '', $meta->title);
    }

    protected function order(SplFileInfo $file, DocumentMetadata $meta): int
    {
        return preg_match('/^(\d+)/', $file->getFilename(), $matches) === 1 ? (int) $matches[1] : 0;
    }

    protected function date(SplFileInfo $file, DocumentMetadata $meta): ?CarbonImmutable
    {
        $fromBase = parent::date($file, $meta);

        if ($fromBase instanceof CarbonImmutable) {
            return $fromBase;
        }

        $inline = $this->inline($meta->body, 'Date');

        if ($inline !== null && preg_match('/(\d{4}-\d{2}-\d{2})/', $inline, $matches) === 1) {
            try {
                return CarbonImmutable::parse($matches[1]);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function metadata(string $content, DocumentMetadata $meta): AdrMetadata
    {
        $status = AdrStatus::fromRaw($meta->string('status') ?? $this->inline($content, 'Status'));

        $deciders = $meta->list('deciders');

        if ($deciders === []) {
            $inline = $this->inline($content, 'Deciders');

            if ($inline !== null) {
                $deciders = array_values(array_filter(array_map(
                    mb_trim(...),
                    (array) preg_split('/[,;]/', $inline),
                ), static fn (string $name): bool => $name !== ''));
            }
        }

        return new AdrMetadata($status, $deciders, $this->relations($content, $meta));
    }

    /**
     * Read an inline `**Key:** value` line from the body.
     */
    private function inline(string $content, string $key): ?string
    {
        if (preg_match('/\*\*'.preg_quote($key, '/').':\*\*\s*(.+)/', $content, $matches) === 1) {
            return mb_trim($matches[1]);
        }

        return null;
    }

    /**
     * @return list<array{label: string, target: string}>
     */
    private function relations(string $content, DocumentMetadata $meta): array
    {
        $relations = [];

        $related = $meta->frontMatter['related'] ?? null;

        if (is_array($related)) {
            foreach ($related as $label => $target) {
                if (is_string($target) && mb_trim($target) !== '') {
                    $relations[] = [
                        'label' => is_string($label) ? ucfirst($label) : 'Relacionado',
                        'target' => mb_trim($target),
                    ];
                }
            }
        }

        foreach (['Builds on', 'Superseded by'] as $label) {
            $inline = $this->inline($content, $label);

            if ($inline !== null) {
                $relations[] = ['label' => $label, 'target' => $inline];
            }
        }

        return $relations;
    }
}
