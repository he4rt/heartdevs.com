<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Build orphan→kept user mapping from dedup pairs
        $mappings = DB::select('
            SELECT
                old_ei.model_id as orphan_user_id,
                new_ei.model_id as kept_user_id
            FROM external_identities old_ei
            JOIN external_identities new_ei
                ON old_ei.external_account_id = new_ei.external_account_id
                AND old_ei.provider = new_ei.provider
                AND old_ei.id != new_ei.id
            WHERE old_ei.deleted_at IS NOT NULL
              AND new_ei.deleted_at IS NULL
        ');

        $orphanToKept = [];
        foreach ($mappings as $m) {
            $orphanToKept[$m->orphan_user_id] = $m->kept_user_id;
        }

        // Collect ALL orphan user IDs (including 26 with no EI at all)
        $orphanUserIds = DB::table('users')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('external_identities')
                    ->whereColumn('external_identities.model_id', DB::raw('users.id::text'))
                    ->whereNull('external_identities.deleted_at');
            })
            ->pluck('id');

        if ($orphanUserIds->isEmpty()) {
            return;
        }

        // === PHASE 1: Migrate data from orphan → kept user ===

        // 1a. Merge user_information: fill empty fields on kept user from orphan
        foreach ($orphanToKept as $orphanId => $keptId) {
            $orphanInfo = DB::table('user_information')->where('user_id', $orphanId)->first();
            $keptInfo = DB::table('user_information')->where('user_id', $keptId)->first();

            if (!$orphanInfo || !$keptInfo) {
                continue;
            }

            $fields = ['name', 'nickname', 'github_url', 'linkedin_url', 'about', 'birthdate'];
            $updates = [];

            foreach ($fields as $field) {
                if (blank($keptInfo->{$field}) && filled($orphanInfo->{$field})) {
                    $updates[$field] = $orphanInfo->{$field};
                }
            }

            if ($updates !== []) {
                DB::table('user_information')->where('user_id', $keptId)->update($updates);
            }
        }

        // 1b. Reassign feedbacks: point sender_id/target_id from orphan → kept user
        foreach ($orphanToKept as $orphanId => $keptId) {
            DB::table('feedbacks')
                ->where('sender_id', $orphanId)
                ->update(['sender_id' => $keptId]);

            DB::table('feedbacks')
                ->where('target_id', $orphanId)
                ->update(['target_id' => $keptId]);
        }

        // === PHASE 2: Clean up NO ACTION FK references ===

        $orphanCharacterIds = DB::table('characters')
            ->whereIn('user_id', $orphanUserIds)
            ->pluck('id');

        DB::table('characters_leveling_logs')
            ->whereIn('character_id', $orphanCharacterIds)
            ->delete();

        DB::table('event_submission_speakers')
            ->whereIn('user_id', $orphanUserIds)
            ->delete();

        // === PHASE 3: Clean up orphan records ===

        DB::table('external_identities')
            ->whereIn('model_id', $orphanUserIds->map(fn ($id) => (string) $id))
            ->whereNotNull('deleted_at')
            ->delete();

        DB::table('media')
            ->where('model_type', 'user')
            ->whereIn('model_id', $orphanUserIds->map(fn ($id) => (string) $id))
            ->delete();

        // Delete orphan users — cascades to:
        // characters (→ characters_badges, characters_wallet),
        // user_information, user_address, tenant_users,
        // events_attendees, events_talks, feedback_reviews,
        // meetings, meeting_participants
        DB::table('users')
            ->whereIn('id', $orphanUserIds)
            ->delete();
    }

    public function down(): void
    {
        // Irreversible: orphan data has been permanently deleted.
    }
};
