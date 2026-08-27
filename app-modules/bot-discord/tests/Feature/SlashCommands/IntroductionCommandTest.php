<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use He4rt\Profile\Actions\UpsertProfile;
use He4rt\Profile\DTOs\UpsertProfileDTO;
use He4rt\Profile\Models\Profile;

describe('persistence flow', static function (): void {
    test('resolves user context and upserts profile on introduction', function (): void {
        $userDto = ResolveUserProviderDTO::make([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '286313989237899276',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'testuser',
            'avatar' => 'https://cdn.discordapp.com/avatars/286313989237899276/test.png',
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        $userContext->user->update(['name' => 'TestName']);

        $profile = Profile::ensureExists($userContext->user->id);

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

    test('new user gets a profile automatically via ResolveUserContext', function (): void {
        $userDto = ResolveUserProviderDTO::make([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '999999999999999999',
            'model_type' => (new User)->getMorphClass(),
            'username' => 'newuser',
            'avatar' => null,
        ]);

        $userContext = resolve(ResolveUserContext::class)->handle($userDto);

        $profile = Profile::query()
            ->where('user_id', $userContext->user->id)
            ->first();

        expect($profile)->not->toBeNull();
    });
});
