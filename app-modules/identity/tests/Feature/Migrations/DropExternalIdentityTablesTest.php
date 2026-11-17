<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function dropExternalIdentityTables(): Migration
{
    return require __DIR__.'/../../../database/migrations/external-identity/2026_11_11_183927_drop_external_identity_tables.php';
}

function permissionFor(string $name): Permission
{
    return Permission::query()->create([
        'name' => $name,
        'guard_name' => 'web',
        'resource' => 'resource',
        'action' => 'view',
        'resource_group' => 'Identity',
    ]);
}

it('drops the tables and the permissions of the removed team integrations', function (): void {
    Schema::create('identity_external_identities', static function (Blueprint $table): void {
        $table->uuid('id')->primary();
    });
    Schema::create('identity_external_identity_resources', static function (Blueprint $table): void {
        $table->uuid('id')->primary();
    });
    permissionFor('view_external_identity');
    permissionFor('delete_identity_resource');
    permissionFor('view_team_report');

    dropExternalIdentityTables()->up();

    expect(Schema::hasTable('identity_external_identities'))->toBeFalse()
        ->and(Schema::hasTable('identity_external_identity_resources'))->toBeFalse()
        ->and(Permission::query()->pluck('name')->all())
        ->not->toContain('view_external_identity', 'delete_identity_resource')
        ->toContain('view_team_report');
});

it('runs on a database that never had the tables', function (): void {
    dropExternalIdentityTables()->up();

    expect(Schema::hasTable('identity_external_identities'))->toBeFalse();
});
