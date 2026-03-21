<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // 1. Drop all FK constraints referencing providers
        // ──────────────────────────────────────────────
        Schema::table('provider_tokens', function (Blueprint $table): void {
            $table->dropForeign(['provider_id']);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['provider_id']);
        });

        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->dropForeign(['provider_id']);
        });

        // ──────────────────────────────────────────────
        // 2. Add new columns to providers table
        // ──────────────────────────────────────────────
        Schema::table('providers', function (Blueprint $table): void {
            $table->string('type')->default('external')->after('model_id');
            $table->string('credentials_type')->default('oauth2')->after('provider');
            $table->text('credentials')->nullable()->after('credentials_type');
            $table->uuid('connected_by')->nullable()->after('provider_id');
            $table->timestamp('connected_at')->nullable()->after('connected_by');
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
            $table->json('metadata')->nullable()->after('disconnected_at');
            $table->softDeletes();
        });

        // ──────────────────────────────────────────────
        // 3. Set defaults for existing rows
        // ──────────────────────────────────────────────
        DB::table('providers')->update([
            'type' => 'external',
            'credentials_type' => 'oauth2',
        ]);

        // Set connected_at = created_at for all existing records
        DB::statement('UPDATE providers SET connected_at = created_at');

        // ──────────────────────────────────────────────
        // 4. Migrate provider_tokens → credentials column
        //    Encrypt each value since ClientAccessManager
        //    getters call Crypt::decrypt()
        // ──────────────────────────────────────────────
        $tokens = DB::table('provider_tokens')
            ->select('provider_id', 'access_token', 'refresh_token', 'expires_in')
            ->get();

        foreach ($tokens as $token) {
            $credentials = json_encode([
                'client_id' => null,
                'client_secret' => null,
                'access_token' => $token->access_token
                    ? Crypt::encrypt($token->access_token)
                    : null,
                'refresh_token' => $token->refresh_token
                    ? Crypt::encrypt($token->refresh_token)
                    : null,
                'expires_in' => $token->expires_in !== null
                    ? Crypt::encrypt((string) $token->expires_in)
                    : null,
                'username' => null,
                'password' => null,
                'api_key' => null,
            ]);

            // provider_tokens.provider_id is a UUID FK to providers.id
            DB::table('providers')
                ->where('id', $token->provider_id)
                ->update(['credentials' => $credentials]);
        }

        // Set empty credentials JSON for rows without tokens
        DB::table('providers')
            ->whereNull('credentials')
            ->update([
                'credentials' => json_encode([
                    'client_id' => null,
                    'client_secret' => null,
                    'access_token' => null,
                    'refresh_token' => null,
                    'expires_in' => null,
                    'username' => null,
                    'password' => null,
                    'api_key' => null,
                ]),
            ]);

        // ──────────────────────────────────────────────
        // 5. Migrate email/avatar/username → metadata JSON
        // ──────────────────────────────────────────────
        $providers = DB::table('providers')
            ->select('id', 'email', 'avatar', 'username')
            ->get();

        foreach ($providers as $provider) {
            $metadata = json_encode(array_filter([
                'email' => $provider->email,
                'avatar' => $provider->avatar,
                'username' => $provider->username,
            ]));

            DB::table('providers')
                ->where('id', $provider->id)
                ->update(['metadata' => $metadata]);
        }

        // ──────────────────────────────────────────────
        // 6. Drop old profile columns
        // ──────────────────────────────────────────────
        Schema::table('providers', function (Blueprint $table): void {
            $table->dropColumn(['email', 'avatar', 'username']);
        });

        // ──────────────────────────────────────────────
        // 7. Rename provider_id → external_account_id
        //    (this is the external platform ID column,
        //     NOT the UUID FK in other tables)
        // ──────────────────────────────────────────────
        Schema::table('providers', function (Blueprint $table): void {
            $table->renameColumn('provider_id', 'external_account_id');
        });

        // ──────────────────────────────────────────────
        // 8. Rename table: providers → external_identities
        // ──────────────────────────────────────────────
        Schema::rename('providers', 'external_identities');

        // ──────────────────────────────────────────────
        // 9. Rename FK columns in dependent tables
        //    (these are UUID FKs, NOT the external ID)
        // ──────────────────────────────────────────────
        Schema::table('messages', function (Blueprint $table): void {
            $table->renameColumn('provider_id', 'external_identity_id');
        });

        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->renameColumn('provider_id', 'external_identity_id');
        });

        // ──────────────────────────────────────────────
        // 10. Re-add FK constraints pointing to new table
        // ──────────────────────────────────────────────
        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign('external_identity_id')
                ->references('id')
                ->on('external_identities');
        });

        Schema::table('voice_messages', function (Blueprint $table): void {
            $table->foreign('external_identity_id')
                ->references('id')
                ->on('external_identities');
        });

        // ──────────────────────────────────────────────
        // 11. Add connected_by FK constraint
        // ──────────────────────────────────────────────
        Schema::table('external_identities', function (Blueprint $table): void {
            $table->foreign('connected_by')
                ->references('id')
                ->on('users');
        });

        // ──────────────────────────────────────────────
        // 12. Drop provider_tokens table
        // ──────────────────────────────────────────────
        Schema::dropIfExists('provider_tokens');
    }
};
