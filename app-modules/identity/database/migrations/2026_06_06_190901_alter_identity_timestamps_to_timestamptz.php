<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // users
        $this->alterColumn('users', 'created_at');
        $this->alterColumn('users', 'updated_at');
        $this->alterColumn('users', 'first_login_at');

        // password_resets
        $this->alterColumn('password_resets', 'created_at');

        // user_address
        $this->alterColumn('user_address', 'created_at');
        $this->alterColumn('user_address', 'updated_at');

        // user_information
        $this->alterColumn('user_information', 'created_at');
        $this->alterColumn('user_information', 'updated_at');

        // tenants
        $this->alterColumn('tenants', 'created_at');
        $this->alterColumn('tenants', 'updated_at');
        $this->alterColumn('tenants', 'deleted_at');

        // tenant_users
        $this->alterColumn('tenant_users', 'created_at');
        $this->alterColumn('tenant_users', 'updated_at');

        // external_identities
        $this->alterColumn('external_identities', 'created_at');
        $this->alterColumn('external_identities', 'updated_at');
        $this->alterColumn('external_identities', 'deleted_at');
        $this->alterColumn('external_identities', 'connected_at');
        $this->alterColumn('external_identities', 'disconnected_at');
    }

    /**
     * @param  'America/Sao_Paulo'|'UTC'  $timezone
     */
    private function alterColumn(string $table, string $column, string $timezone = 'America/Sao_Paulo'): void
    {
        $isTimestamp = DB::scalar(
            "SELECT data_type = 'timestamp without time zone'
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            [$table, $column],
        );

        if (!$isTimestamp) {
            return;
        }

        DB::statement(
            sprintf("ALTER TABLE %s ALTER COLUMN \"%s\" TYPE timestamptz USING \"%s\" AT TIME ZONE '%s'", $table, $column, $column, $timezone)
        );
    }
};
