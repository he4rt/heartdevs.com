<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class AuditMorphColumns extends Command
{
    private const array MORPH_TABLES = [
        'external_identities' => 'model_type',
        'wallets' => 'owner_type',
        'transactions' => 'reference_type',
        'events_agenda' => 'schedulable_type',
        'media' => 'model_type',
        'notifications' => 'notifiable_type',
        'personal_access_tokens' => 'tokenable_type',
    ];
    protected $signature = 'morph:audit';

    protected $description = 'Audit all polymorphic *_type columns and report distinct values';

    public function handle(): void
    {
        intro('Polymorphic Column Audit');

        $allRows = [];

        foreach (self::MORPH_TABLES as $tableName => $column) {
            if (! Schema::hasTable($tableName)) {
                $allRows[] = [$tableName, $column, '(table not found)', '-'];

                continue;
            }

            if (! Schema::hasColumn($tableName, $column)) {
                $allRows[] = [$tableName, $column, '(column not found)', '-'];

                continue;
            }

            $distinctValues = DB::table($tableName)
                ->select($column, DB::raw('COUNT(*) as cnt'))
                ->groupBy($column)
                ->orderByDesc('cnt')
                ->get();

            if ($distinctValues->isEmpty()) {
                $allRows[] = [$tableName, $column, '(empty table)', '0'];

                continue;
            }

            foreach ($distinctValues as $row) {
                $value = $row->{$column} ?? '(NULL)';
                $allRows[] = [$tableName, $column, $value, number_format($row->cnt)];
            }
        }

        table(
            headers: ['Table', 'Column', 'Distinct Value', 'Count'],
            rows: $allRows,
        );

        // Summary
        $fqcnCount = 0;
        $aliasCount = 0;
        foreach ($allRows as $row) {
            if (str_contains($row[2], '\\')) {
                $fqcnCount++;
            } elseif (! str_starts_with($row[2], '(')) {
                $aliasCount++;
            }
        }

        info(sprintf('FQCN entries: %d | Alias entries: %d', $fqcnCount, $aliasCount));

        if ($fqcnCount > 0) {
            warning('Database contains FQCN values that need migration to aliases.');
        } else {
            outro('All morph columns use aliases. Ready for enforceMorphMap.');
        }
    }
}
