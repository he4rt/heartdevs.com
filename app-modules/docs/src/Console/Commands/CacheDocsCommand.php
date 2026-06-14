<?php

declare(strict_types=1);

namespace He4rt\Docs\Console\Commands;

use He4rt\Docs\Discovery\DocumentRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Warms or clears the documentation portal cache. Run `docs:cache` during
 * deploy to pre-build the navigation tree and rendered pages.
 */
#[Description('Warm or clear the documentation portal cache')]
#[Signature('docs:cache {--clear : Clear the cached documentation index instead of warming it}')]
final class CacheDocsCommand extends Command
{
    public function handle(DocumentRegistry $registry): int
    {
        if ($this->option('clear')) {
            $registry->forget();
            $this->info('Documentation cache cleared.');

            return self::SUCCESS;
        }

        $count = $registry->warm();
        $this->info(sprintf('Documentation cache warmed: %d document(s).', $count));

        return self::SUCCESS;
    }
}
