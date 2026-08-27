<?php

declare(strict_types=1);

namespace App\Tasks\Cleanup\Strategies;

use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

class SimpleStrategy extends CleanupStrategy
{
    public function deleteOldBackups(BackupCollection $backups): void
    {
        $keepOldBackupsCount = config('backup.cleanup.simple_strategy.keep_old_backups_count');

        if (is_null($keepOldBackupsCount)) {
            return;
        }

        $backups->shift();

        if ($keepOldBackupsCount === 0) {
            $backups->each(fn (Backup $backup) => $backup->delete());
        }

        if ($backups->count() > $keepOldBackupsCount) {
            $backups
                ->slice($keepOldBackupsCount)
                ->each(fn (Backup $backup) => $backup->delete());
        }
    }
}
