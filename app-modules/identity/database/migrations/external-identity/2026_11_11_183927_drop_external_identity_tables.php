<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The team integrations were removed. A database that ran their migrations still has the tables and the
     * permissions that sync:permissions created for both models, and sync:permissions does not delete those.
     */
    public function up(): void
    {
        Schema::dropIfExists('identity_external_identity_resources');
        Schema::dropIfExists('identity_external_identities');

        DB::table('identity_permissions')
            ->where('name', 'like', '%\_external\_identity')
            ->orWhere('name', 'like', '%\_identity\_resource')
            ->delete();

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
