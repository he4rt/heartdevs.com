<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\warning;

#[Description('Fix post-switch timestamp data after ALTER migrations (Option D Step 2)')]
#[Signature('maintenance:fix-post-switch-timestamps
        {--dry-run : Show what would be done without altering data}
        {--module= : Process only a specific module}
        {--table= : Process only a specific table}')]
final class FixPostSwitchTimestampsCommand extends Command
{
    private const string CUTOFF = '2026-05-20 20:04:16+00';

    public function handle(): int
    {
        $totalStart = microtime(true);

        intro('Fix post-switch timestamps (Option D — Step 2)');

        if (!$this->validateSchemaReady()) {
            return self::FAILURE;
        }

        $this->showConfig();

        if (!$this->option('dry-run') && !confirm('Proceed with data fix?', default: false)) {
            warning('Aborted.');

            return self::FAILURE;
        }

        $allStats = [];
        $modulesToProcess = $this->getModulesToProcess();

        foreach ($modulesToProcess as $name => $tables) {
            $allStats[$name] = $this->processModule($name, $tables);
        }

        $this->showSummary($allStats);

        $this->runVerification();

        $total = microtime(true) - $totalStart;
        outro(sprintf('Done in %.1fs', $total));

        return self::SUCCESS;
    }

    private function validateSchemaReady(): bool
    {
        $remaining = DB::select(
            "SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND data_type = 'timestamp without time zone'
             ORDER BY table_name, column_name",
        );

        if ($remaining === []) {
            return true;
        }

        warning(sprintf(
            'Found %d column(s) still using "timestamp without time zone". ALTER migrations must run first.',
            count($remaining),
        ));

        table(
            headers: ['Table', 'Column'],
            rows: array_map(fn ($row) => [$row->table_name, $row->column_name], $remaining),
        );

        return false;
    }

    private function showConfig(): void
    {
        $moduleFilter = $this->option('module') ?? 'all';
        $tableFilter = $this->option('table') ?? 'all';

        table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Cutoff', self::CUTOFF],
                ['Fix', "SET col = col - interval '3 hours' WHERE col >= CUTOFF"],
                ['Dry run', $this->option('dry-run') ? 'Yes' : 'No'],
                ['Database', DB::getDatabaseName()],
                ['Module filter', $moduleFilter],
                ['Table filter', $tableFilter],
            ],
        );
    }

    /**
     * @return array<string, array<string, list<list<string>>>>
     */
    private function getModulesToProcess(): array
    {
        $modules = $this->modules();
        $moduleFilter = $this->option('module');
        $tableFilter = $this->option('table');

        if ($moduleFilter !== null) {
            $modules = array_filter(
                $modules,
                fn (string $key) => $key === $moduleFilter,
                ARRAY_FILTER_USE_KEY,
            );
        }

        if ($tableFilter !== null) {
            foreach ($modules as $name => $tables) {
                $modules[$name] = array_filter(
                    $tables,
                    fn (string $key) => $key === $tableFilter,
                    ARRAY_FILTER_USE_KEY,
                );

                if ($modules[$name] === []) {
                    unset($modules[$name]);
                }
            }
        }

        return $modules;
    }

    /**
     * @param  array<string, list<list<string>>>  $tables
     * @return array{tables: int, columns: int, rows: int, elapsed: float}
     */
    private function processModule(string $name, array $tables): array
    {
        $moduleStats = ['tables' => 0, 'columns' => 0, 'rows' => 0, 'elapsed' => 0.0];

        task(
            label: $name.': processing...',
            callback: function ($logger) use ($name, $tables, &$moduleStats): void {
                $moduleStart = microtime(true);

                foreach ($tables as $tableName => $columns) {
                    $moduleStats['tables']++;

                    foreach ($columns as $colDef) {
                        $column = $colDef[0];
                        $fixScope = $colDef[1] ?? 'column';
                        $moduleStats['columns']++;

                        $result = $this->fixColumn($tableName, $column, $fixScope);
                        $moduleStats['rows'] += $result['rows'];

                        $action = $this->option('dry-run') ? 'would fix' : 'UPDATE';
                        $logger->line(sprintf(
                            '  %s.%-35s %s %s rows  %sms',
                            $tableName,
                            $column,
                            $action,
                            number_format($result['rows']),
                            number_format($result['elapsed'], 0),
                        ));
                    }
                }

                $moduleStats['elapsed'] = (microtime(true) - $moduleStart) * 1000;
                $logger->label(sprintf(
                    '%s: %d tables, %d cols, %s rows — %.1fs',
                    $name,
                    $moduleStats['tables'],
                    $moduleStats['columns'],
                    number_format($moduleStats['rows']),
                    $moduleStats['elapsed'] / 1000,
                ));
            },
        );

        return $moduleStats;
    }

    /**
     * @param  'column'|'row'  $fixScope
     * @return array{rows: int, elapsed: float}
     */
    private function fixColumn(string $table, string $column, string $fixScope = 'column'): array
    {
        $start = microtime(true);
        $whereCol = $fixScope === 'row' ? 'created_at' : sprintf('"%s"', $column);
        $nullGuard = $fixScope === 'row' ? sprintf(' AND "%s" IS NOT NULL', $column) : '';

        if ($this->option('dry-run')) {
            $count = (int) DB::scalar(
                sprintf('SELECT count(*) FROM %s WHERE %s >= ?%s', $table, $whereCol, $nullGuard),
                [self::CUTOFF],
            );

            return ['rows' => $count, 'elapsed' => (microtime(true) - $start) * 1000];
        }

        $rowsFixed = DB::affectingStatement(
            sprintf("UPDATE %s SET \"%s\" = \"%s\" - interval '3 hours' WHERE %s >= ?%s", $table, $column, $column, $whereCol, $nullGuard),
            [self::CUTOFF],
        );

        return ['rows' => $rowsFixed, 'elapsed' => (microtime(true) - $start) * 1000];
    }

    /**
     * @param  array<string, array{tables: int, columns: int, rows: int, elapsed: float}>  $allStats
     */
    private function showSummary(array $allStats): void
    {
        $rows = [];
        $totalTables = 0;
        $totalColumns = 0;
        $totalRows = 0;
        $totalElapsed = 0.0;

        foreach ($allStats as $module => $stats) {
            $rows[] = [
                $module,
                (string) $stats['tables'],
                (string) $stats['columns'],
                number_format($stats['rows']),
                sprintf('%.1fs', $stats['elapsed'] / 1000),
            ];
            $totalTables += $stats['tables'];
            $totalColumns += $stats['columns'];
            $totalRows += $stats['rows'];
            $totalElapsed += $stats['elapsed'];
        }

        $rows[] = [
            'TOTAL',
            (string) $totalTables,
            (string) $totalColumns,
            number_format($totalRows),
            sprintf('%.1fs', $totalElapsed / 1000),
        ];

        table(
            headers: ['Module', 'Tables', 'Columns', 'Rows', 'Time'],
            rows: $rows,
        );
    }

    private function runVerification(): void
    {
        $remaining = DB::select(
            "SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND data_type = 'timestamp without time zone'
             ORDER BY table_name, column_name",
        );

        if ($remaining === []) {
            table(
                headers: ['Verification'],
                rows: [['0 columns with "timestamp without time zone" — schema is clean']],
            );

            return;
        }

        warning(sprintf('%d column(s) still using "timestamp without time zone":', count($remaining)));

        table(
            headers: ['Table', 'Column'],
            rows: array_map(fn ($row) => [$row->table_name, $row->column_name], $remaining),
        );
    }

    /**
     * @return array<string, array<string, list<list<string>>>>
     */
    private function modules(): array
    {
        return [
            'identity' => [
                'users' => [['created_at'], ['updated_at'], ['first_login_at']],
                'password_resets' => [['created_at']],
                'user_address' => [['created_at'], ['updated_at']],
                'user_information' => [['created_at'], ['updated_at']],
                'tenants' => [['created_at'], ['updated_at'], ['deleted_at']],
                'tenant_users' => [['created_at'], ['updated_at']],
                'external_identities' => [['created_at'], ['updated_at'], ['deleted_at'], ['connected_at'], ['disconnected_at']],
            ],
            'activity' => [
                'messages' => [['created_at'], ['updated_at'], ['edited_at']],
                'voice_messages' => [['created_at'], ['updated_at']],
                'interactions' => [['created_at'], ['updated_at'], ['occurred_at'], ['reviewed_at']],
                'moderation_events' => [['created_at'], ['updated_at'], ['occurred_at']],
                'activity_reactions' => [['created_at'], ['updated_at']],
                'message_mentions' => [['created_at'], ['updated_at']],
                'message_threads' => [['created_at'], ['updated_at']],
                'message_attachments' => [['created_at'], ['updated_at']],
                'message_embeds' => [['created_at'], ['updated_at']],
                'membership_events' => [['created_at'], ['updated_at'], ['occurred_at']],
                'activity_timeline' => [['created_at'], ['updated_at']],
                'activity_post_entries' => [['created_at'], ['updated_at'], ['deleted_at']],
            ],
            'gamification' => [
                'characters' => [['created_at'], ['updated_at'], ['daily_bonus_claimed_at']],
                'badges' => [['created_at'], ['updated_at']],
                'characters_badges' => [['claimed_at']],
                'seasons_rankings' => [['created_at'], ['updated_at']],
                'seasons' => [['created_at'], ['updated_at'], ['started_at', 'row'], ['ended_at', 'row']],
                'characters_leveling_logs' => [['created_at'], ['updated_at']],
            ],
            'integration-discord' => [
                'discord_event_logs' => [['created_at'], ['updated_at']],
                'discord_guilds' => [['created_at'], ['updated_at'], ['synced_at']],
                'discord_channels' => [['created_at'], ['updated_at']],
                'discord_roles' => [['created_at'], ['updated_at']],
                'discord_members' => [['created_at'], ['updated_at'], ['left_at']],
                'discord_member_roles' => [['created_at'], ['updated_at'], ['assigned_at']],
                'discord_member_role_history' => [['created_at'], ['updated_at'], ['occurred_at']],
            ],
            'community' => [
                'meeting_types' => [['created_at'], ['updated_at']],
                'meetings' => [['created_at'], ['updated_at'], ['starts_at', 'row'], ['ends_at', 'row']],
                'meeting_participants' => [['created_at'], ['updated_at'], ['attend_at']],
                'feedbacks' => [['created_at'], ['updated_at']],
                'feedback_reviews' => [['created_at'], ['updated_at'], ['received_at']],
            ],
            'economy' => [
                'wallets' => [['created_at'], ['updated_at']],
                'transactions' => [['created_at'], ['updated_at']],
            ],
            'main' => [
                'personal_access_tokens' => [['created_at'], ['updated_at'], ['last_used_at'], ['expires_at', 'row']],
                'notifications' => [['created_at'], ['updated_at'], ['read_at']],
                'media' => [['created_at'], ['updated_at']],
                'failed_jobs' => [['failed_at']],
                'telescope_entries' => [['created_at']],
            ],
        ];
    }
}
