<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @return array<string, User|Collection<int, User>|Collection<int, Tenant>|Tenant|Collection<int, Profile>|Profile>
 */
function createEditProfileScenario(array $profileOverrides = []): array
{
    $user = User::factory()->create(['name' => 'OldName']);
    $tenant = Tenant::factory()->create();

    $user->providers()->create([
        'provider' => IdentityProvider::Discord->value,
        'external_account_id' => '286313989237899276',
        'tenant_id' => $tenant->id,
        'type' => 'external',
        'credentials_type' => 'oauth2',
        'credentials' => ClientAccessManager::make(),
    ]);

    $profile = Profile::factory()->create(array_merge([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
    ], $profileOverrides));

    return ['user' => $user, 'tenant' => $tenant, 'profile' => $profile];
}

describe('persistence flow', static function (): void {
    test('updates user name and profile data', function (): void {
        $data = createEditProfileScenario([
            'nickname' => 'OldNick',
            'about' => 'Old about text here',
        ]);

        $data['user']->update(['name' => 'NewName']);

        $profile = Profile::query()
            ->where('user_id', $data['user']->id)
            ->where('tenant_id', $data['tenant']->id)
            ->firstOrFail();

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => 'NewNick',
            'about' => 'New about text is here',
        ]);

        resolve(UpsertProfile::class)->handle($profile, $dto);

        $data['user']->refresh();
        $profile->refresh();

        expect($data['user']->name)->toBe('NewName')
            ->and($profile->nickname)->toBe('NewNick')
            ->and($profile->about)->toBe('New about text is here');
    });

    test('throws when profile does not exist for user', function (): void {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        Profile::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();
    })->throws(ModelNotFoundException::class);

    test('handles partial update without overwriting existing fields', function (): void {
        $data = createEditProfileScenario([
            'nickname' => 'ExistingNick',
            'about' => 'Existing about text.',
            'headline' => 'Existing headline',
        ]);

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => 'UpdatedNick',
            'about' => 'Updated about text.',
        ]);

        resolve(UpsertProfile::class)->handle($data['profile'], $dto);

        $data['profile']->refresh();

        expect($data['profile']->nickname)->toBe('UpdatedNick')
            ->and($data['profile']->about)->toBe('Updated about text.')
            ->and($data['profile']->headline)->toBe('Existing headline');
    });

    test('persists correctly when profile had null nickname and about', function (): void {
        $data = createEditProfileScenario([
            'nickname' => null,
            'about' => null,
        ]);

        $dto = UpsertProfileDTO::fromArray([
            'nickname' => 'BrandNew',
            'about' => 'Hello, I am new here!',
        ]);

        resolve(UpsertProfile::class)->handle($data['profile'], $dto);

        $data['profile']->refresh();

        expect($data['profile']->nickname)->toBe('BrandNew')
            ->and($data['profile']->about)->toBe('Hello, I am new here!');
    });
});
