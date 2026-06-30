<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @return array<string, Collection<int, Tenant>|Tenant>
 */
function createIntroductionScenario(): array
{
    $tenant = Tenant::factory()->create();

    $tenant->providers()->create([
        'provider' => IdentityProvider::Discord->value,
        'external_account_id' => '540204204242206721',
        'tenant_id' => $tenant->id,
        'type' => 'external',
        'credentials_type' => 'oauth2',
        'credentials' => ClientAccessManager::make(),
    ]);

    return ['tenant' => $tenant];
}

describe('persistence flow', static function (): void {
    test('resolves user context and upserts profile on introduction', function (): void {
        $data = createIntroductionScenario();

        $tenantProvider = ExternalIdentity::query()
            ->where('model_type', (new Tenant)->getMorphClass())
            ->where('external_account_id', '540204204242206721')
            ->firstOrFail();

        $userDto = ResolveUserProviderDTO::make([
            'tenant_id' => $tenantProvider->tenant_id,
            'provider' => $tenantProvider->provider,
            'external_account_id' => '286313989237899276',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'testuser',
            'avatar' => 'https://cdn.discordapp.com/avatars/286313989237899276/test.png',
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        $userContext->user->update(['name' => 'TestName']);

        $profile = Profile::query()
            ->where('user_id', $userContext->user->id)
            ->where('tenant_id', $tenantProvider->tenant_id)
            ->firstOrFail();

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => 'testnick',
            'about' => 'I joined He4rt because I love coding!',
        ]);

        resolve(UpsertProfile::class)->handle($profile, $dto);

        $userContext->user->refresh();
        $profile->refresh();

        expect($userContext->user->name)->toBe('TestName')
            ->and($profile->nickname)->toBe('testnick')
            ->and($profile->about)->toBe('I joined He4rt because I love coding!');
    });

    test('tenant provider lookup fails when guild is not registered', function (): void {
        ExternalIdentity::query()
            ->where('model_type', (new Tenant)->getMorphClass())
            ->where('external_account_id', '999999999999999999')
            ->firstOrFail();
    })->throws(ModelNotFoundException::class);

    test('new user gets tenant pivot and profile via ResolveUserContext', function (): void {
        $data = createIntroductionScenario();

        $tenantProvider = ExternalIdentity::query()
            ->where('model_type', (new Tenant)->getMorphClass())
            ->where('external_account_id', '540204204242206721')
            ->firstOrFail();

        $userDto = ResolveUserProviderDTO::make([
            'tenant_id' => $tenantProvider->tenant_id,
            'provider' => $tenantProvider->provider,
            'external_account_id' => '999999999999999999',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'newuser',
            'avatar' => null,
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        expect($userContext->user->tenants()->where('tenants.id', $data['tenant']->id)->exists())->toBeTrue();

        $profile = Profile::query()
            ->where('user_id', $userContext->user->id)
            ->where('tenant_id', $tenantProvider->tenant_id)
            ->first();

        expect($profile)->not->toBeNull();
    });
});
