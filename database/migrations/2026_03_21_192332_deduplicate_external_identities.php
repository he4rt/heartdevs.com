<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Find all duplicate pairs: same external_account_id+provider,
        // old (double-backslash model_type, len=25) vs new (single-backslash, len=22)
        $duplicates = DB::select('
            SELECT
                old_ei.id as old_ei_id,
                old_ei.model_id as old_user_id,
                new_ei.id as new_ei_id,
                new_ei.model_id as new_user_id
            FROM external_identities old_ei
            JOIN external_identities new_ei
                ON old_ei.external_account_id = new_ei.external_account_id
                AND old_ei.provider = new_ei.provider
                AND old_ei.id != new_ei.id
            WHERE LENGTH(old_ei.model_type) = 25
              AND LENGTH(new_ei.model_type) = 22
              AND old_ei.deleted_at IS NULL
              AND new_ei.deleted_at IS NULL
        ');

        foreach ($duplicates as $dup) {
            DB::table('messages')
                ->where('external_identity_id', $dup->old_ei_id)
                ->update(['external_identity_id' => $dup->new_ei_id]);

            DB::table('voice_messages')
                ->where('external_identity_id', $dup->old_ei_id)
                ->update(['external_identity_id' => $dup->new_ei_id]);

            $oldCharacter = DB::table('characters')
                ->where('user_id', $dup->old_user_id)
                ->select(['experience', 'tenant_id'])
                ->first();

            $oldXp = $oldCharacter->experience ?? 0;

            if ($oldXp > 0) {
                $existingCharacter = DB::table('characters')
                    ->where('user_id', $dup->new_user_id)
                    ->exists();

                if ($existingCharacter) {
                    DB::table('characters')
                        ->where('user_id', $dup->new_user_id)
                        ->increment('experience', $oldXp);
                } else {
                    logger()->warning(sprintf('Migration: Creating missing character for user %s with %s XP from old user %s', $dup->new_user_id, $oldXp, $dup->old_user_id));

                    DB::table('characters')->insert([
                        'id' => Str::uuid()->toString(),
                        'user_id' => $dup->new_user_id,
                        'tenant_id' => $oldCharacter->tenant_id,
                        'experience' => $oldXp,
                        'reputation' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('external_identities')
                ->where('id', $dup->old_ei_id)
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Cannot fully reverse XP merge or message reassignment.
        // Restore soft-deleted old EIs only.
        DB::table('external_identities')
            ->whereNotNull('deleted_at')
            ->whereRaw('LENGTH(model_type) = 25')
            ->update(['deleted_at' => null]);
    }
};
