<?php

declare(strict_types=1);

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All FQCN variants that must be normalized to their morph alias.
     * Includes: double-backslash (old import), old namespace, and current namespace.
     */
    private const array MORPH_MAP = [
        'user' => [
            'He4rt\\\\User\\\\Models\\\\User',
            'He4rt\\User\\Models\\User',
            User::class,
        ],
        'tenant' => [
            'He4rt\\Tenant\\Models\\Tenant',
            Tenant::class,
        ],
        'character' => [
            Character::class,
            'He4rt\\Character\\Models\\Character',
        ],
        'badge' => [
            Badge::class,
            'He4rt\\Badge\\Models\\Badge',
        ],
        'event_submission' => [
            'He4rt\\Events\\Models\\EventSubmission',
        ],
        'event_segment' => [
            'He4rt\\Events\\Models\\EventSegment',
        ],
        'sponsor' => [
            'He4rt\\Events\\Models\\Sponsor',
            'He4rt\\Sponsors\\Models\\Sponsor',
        ],
    ];

    private const array TABLES = [
        'external_identities' => 'model_type',
        'wallets' => 'owner_type',
        'transactions' => 'reference_type',
        'events_agenda' => 'schedulable_type',
        'media' => 'model_type',
        'notifications' => 'notifiable_type',
        'personal_access_tokens' => 'tokenable_type',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $column) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (self::MORPH_MAP as $alias => $fqcns) {
                foreach ($fqcns as $fqcn) {
                    DB::table($table)
                        ->where($column, $fqcn)
                        ->update([$column => $alias]);
                }
            }
        }
    }

    public function down(): void
    {
        $reverseMap = [
            'user' => User::class,
            'tenant' => Tenant::class,
            'character' => Character::class,
            'badge' => Badge::class,
            'event_submission' => 'He4rt\\Events\\Models\\EventSubmission',
            'event_segment' => 'He4rt\\Events\\Models\\EventSegment',
            'sponsor' => 'He4rt\\Events\\Models\\Sponsor',
        ];

        foreach (self::TABLES as $table => $column) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($reverseMap as $alias => $fqcn) {
                DB::table($table)
                    ->where($column, $alias)
                    ->update([$column => $fqcn]);
            }
        }
    }
};
