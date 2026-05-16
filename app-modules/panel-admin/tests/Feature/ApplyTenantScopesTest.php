<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Http\Middleware\ApplyTenantScopes;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

test('applies global scope to configured models when tenant is set', function (): void {
    $tenant = Tenant::factory()->create();

    Filament::shouldReceive('getTenant')
        ->andReturn($tenant);

    config(['panel-admin.tenant_scoped_models' => [
        Character::class,
    ]]);

    $middleware = new ApplyTenantScopes();

    $middleware->handle(
        Request::create('/admin'),
        fn () => new Response(),
    );

    $query = Character::query()->toSql();

    expect($query)->toContain('where');
});

test('skips scope application when no tenant is set', function (): void {
    Filament::shouldReceive('getTenant')
        ->andReturn(null);

    config(['panel-admin.tenant_scoped_models' => [
        User::class,
    ]]);

    $middleware = new ApplyTenantScopes();

    $middleware->handle(
        Request::create('/admin'),
        fn () => new Response(),
    );

    $query = User::query()->toSql();

    expect($query)->not->toContain('tenant_id');
});

test('handles empty tenant_scoped_models config', function (): void {
    $tenant = Tenant::factory()->create();

    Filament::shouldReceive('getTenant')
        ->andReturn($tenant);

    config(['panel-admin.tenant_scoped_models' => []]);

    $middleware = new ApplyTenantScopes();

    $response = $middleware->handle(
        Request::create('/admin'),
        fn () => new Response('ok'),
    );

    expect($response->getContent())->toBe('ok');
});
