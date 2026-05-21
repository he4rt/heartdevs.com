<?php

declare(strict_types=1);

namespace He4rt\Profile;

use He4rt\Profile\Listeners\CreateProfileForTenantMember;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class ProfileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Relation::morphMap([
            'profile' => Profile::class,
        ]);

        DB::listen(function (QueryExecuted $event): void {
            if (! preg_match('/^insert into "?tenant_users"?/i', $event->sql)) {
                return;
            }

            $bindings = array_values($event->bindings);

            if (count($bindings) < 2) {
                return;
            }

            $columns = $this->insertColumns($event->sql);
            $tenantIdIndex = array_search('tenant_id', $columns, true);
            $userIdIndex = array_search('user_id', $columns, true);

            if ($tenantIdIndex === false || $userIdIndex === false) {
                return;
            }

            $columnCount = count($columns);
            $listener = $this->app->make(CreateProfileForTenantMember::class);

            foreach (array_chunk($bindings, $columnCount) as $row) {
                if (! isset($row[$tenantIdIndex], $row[$userIdIndex])) {
                    continue;
                }

                $listener->handle((string) $row[$userIdIndex], (int) $row[$tenantIdIndex]);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function insertColumns(string $sql): array
    {
        preg_match('/insert into "?tenant_users"? \((?<columns>[^)]+)\)/i', $sql, $matches);

        if (! isset($matches['columns'])) {
            return [];
        }

        return array_map(
            fn (string $column): string => trim($column, ' "'),
            explode(',', $matches['columns']),
        );
    }
}
