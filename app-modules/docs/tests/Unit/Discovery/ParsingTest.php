<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\Actions\ParseDocumentMetadataAction;
use He4rt\Docs\Discovery\Actions\RenderMarkdownAction;

describe('ParseDocumentMetadataAction', static function (): void {
    it('extracts front-matter and strips it from the body', function (): void {
        $markdown = <<<'MD'
---
title: "Hybrid Pipeline"
module: moderation
deciders:
  - danielhe4rt
  - clinton
---

# Some Heading

Body text.
MD;

        $meta = new ParseDocumentMetadataAction()->execute($markdown, '0001-x.md');

        expect($meta->title)->toBe('Hybrid Pipeline')
            ->and($meta->string('module'))->toBe('moderation')
            ->and($meta->list('deciders'))->toBe(['danielhe4rt', 'clinton'])
            ->and($meta->body)->not->toContain('title:')
            ->and($meta->body)->toContain('# Some Heading');
    });

    it('falls back to the first H1 when there is no front-matter title', function (): void {
        $meta = new ParseDocumentMetadataAction()->execute("# Real Title\n\ntext", 'whatever.md');

        expect($meta->title)->toBe('Real Title');
    });

    it('falls back to a humanized filename without the iso date prefix', function (): void {
        $meta = new ParseDocumentMetadataAction()->execute('no heading here', '2026-05-01-moderation-system-design.md');

        expect($meta->title)->toBe('Moderation System Design');
    });

    it('treats a scalar front-matter value as a single-item list', function (): void {
        $meta = new ParseDocumentMetadataAction()->execute("---\nmodule: identity\n---\n\nx", 'x.md');

        expect($meta->list('module'))->toBe(['identity']);
    });
});

describe('RenderMarkdownAction', static function (): void {
    it('renders html with heading ids and a table of contents', function (): void {
        $rendered = new RenderMarkdownAction()->execute("## Context\n\nText\n\n### Detail\n\nmore");

        expect($rendered->html)->toContain('<h2 id="context">')
            ->and($rendered->html)->toContain('<h3 id="detail">')
            ->and($rendered->toc)->toBe([
                ['level' => 2, 'text' => 'Context', 'id' => 'context'],
                ['level' => 3, 'text' => 'Detail', 'id' => 'detail'],
            ]);
    });

    it('removes front-matter and renders gfm tables', function (): void {
        $rendered = new RenderMarkdownAction()->execute("---\ntitle: X\n---\n\n| a | b |\n|---|---|\n| 1 | 2 |\n");

        expect($rendered->html)->toContain('<table>')
            ->and($rendered->html)->not->toContain('title: X');
    });
});
