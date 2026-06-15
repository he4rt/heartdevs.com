<?php

declare(strict_types=1);

namespace Tests;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function actingAsAdmin(): self
    {
        Tenant::factory()
            ->afterCreating(static function (Tenant $tenant): void {
                ExternalIdentity::factory([
                    'tenant_id' => $tenant->getKey(),
                    'provider' => 'discord',
                    'external_account_id' => '123',
                ])->create();
            })
            ->create();

        return $this->withHeaders([
            'X-He4rt-Authorization' => config('he4rt.server_key'),
            'X-He4rt-Provider' => 'discord',
            'X-He4rt-Provider-Id' => '123',
        ]);
    }
}
