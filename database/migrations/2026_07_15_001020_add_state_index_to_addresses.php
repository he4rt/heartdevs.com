<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The marketing location dashboard (`MembersByState`,
 * `CommunityActivityStats::locatedMembers`) filters `addresses` on
 * `addressable_type = 'user'` and groups by `state`, but only the generic
 * morph index `(addressable_type, addressable_id)` exists. This composite
 * covers both the type predicate and the state grouping.
 *
 * `CREATE INDEX CONCURRENTLY` avoids blocking writes during the build and
 * cannot run inside a transaction.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS addresses_addressable_type_state_index ON addresses (addressable_type, state)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS addresses_addressable_type_state_index');
    }
};
