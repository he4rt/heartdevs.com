<?php

declare(strict_types=1);

use He4rt\Activity\Voice\Actions\RecordVoicePresence;
use He4rt\Activity\Voice\DTOs\RecordVoicePresenceDTO;
use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Activity\Voice\ValueObjects\VoiceTransition;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Actions\ResolveUserContext;
use He4rt\Identity\User\Models\User;
use He4rt\Identity\User\ValueObjects\UserContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{tenant: Tenant, character: Character, user: User, identity: ExternalIdentity}
 */
function seedPresenceUser(string $externalAccountId): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()
        ->recycle($user)
        ->recycle($tenant)
        ->create(['experience' => 4_500]); // level 10

    $identity = ExternalIdentity::factory()
        ->recycle($tenant)
        ->create([
            'model_type' => (new User)->getMorphClass(),
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $externalAccountId,
        ]);

    return ['tenant' => $tenant, 'character' => $character, 'user' => $user, 'identity' => $identity];
}

function presenceDto(Tenant $tenant, string $account, VoicePresenceEnum $presence, string $channelId): RecordVoicePresenceDTO
{
    return new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: $account,
        presence: $presence,
        channelName: $channelId,
        channelId: $channelId,
    );
}

test('joined presence is recorded with zero xp and leaves character xp untouched', function (): void {
    ['tenant' => $tenant, 'character' => $character] = seedPresenceUser('123456');

    resolve(RecordVoicePresence::class)->persistMany([new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '123456',
        presence: VoicePresenceEnum::Joined,
        channelName: 'general-voice',
        channelId: '111222333',
        username: 'testuser',
    )]);

    expect($character->fresh()->experience)->toBe(4_500);

    $voice = Voice::query()->latest('id')->first();
    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('joined')
        ->and($voice->obtained_experience)->toBe(0)
        ->and($voice->channel_name)->toBe('general-voice')
        ->and($voice->channel_id)->toBe('111222333')
        ->and($voice->tenant_id)->toBe($tenant->id)
        ->and($voice->occurred_at)->not->toBeNull();
});

test('left presence is recorded with zero xp', function (): void {
    ['tenant' => $tenant] = seedPresenceUser('789');

    resolve(RecordVoicePresence::class)->persistMany([new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '789',
        presence: VoicePresenceEnum::Left,
        channelName: 'general-voice',
    )]);

    $voice = Voice::query()->latest('id')->first();
    expect($voice)->not->toBeNull()
        ->and($voice->state)->toBe('left')
        ->and($voice->obtained_experience)->toBe(0);
});

test('presence is audio-agnostic: a record is written regardless of mute or deaf state', function (): void {
    // The DTO carries no audio state, so a deafened user joining is recorded
    // exactly like anyone else — proven by the row simply existing.
    ['tenant' => $tenant] = seedPresenceUser('456');

    resolve(RecordVoicePresence::class)->persistMany([new RecordVoicePresenceDTO(
        tenantId: $tenant->id,
        provider: IdentityProvider::Discord,
        externalAccountId: '456',
        presence: VoicePresenceEnum::Joined,
        channelName: 'general-voice',
        channelId: '999888777',
    )]);

    expect(Voice::query()->where('state', 'joined')->count())->toBe(1);
});

test('makeMany fans the shared identity across every transition', function (): void {
    $dtos = RecordVoicePresenceDTO::makeMany(
        tenantId: 'tenant-1',
        provider: IdentityProvider::Discord,
        externalAccountId: '777',
        transitions: [
            VoiceTransition::left('chA', 'A'),
            VoiceTransition::joined('chB', 'B'),
        ],
        username: 'mover',
    );

    expect($dtos)->toHaveCount(2)
        ->and($dtos[0]->presence)->toBe(VoicePresenceEnum::Left)
        ->and($dtos[0]->channelId)->toBe('chA')
        ->and($dtos[0]->channelName)->toBe('A')
        ->and($dtos[1]->presence)->toBe(VoicePresenceEnum::Joined)
        ->and($dtos[1]->channelId)->toBe('chB')
        ->and($dtos[1]->channelName)->toBe('B');

    foreach ($dtos as $dto) {
        expect($dto->tenantId)->toBe('tenant-1')
            ->and($dto->externalAccountId)->toBe('777')
            ->and($dto->username)->toBe('mover');
    }
});

test('makeMany returns an empty list for no transitions', function (): void {
    expect(RecordVoicePresenceDTO::makeMany(
        tenantId: 'tenant-1',
        provider: IdentityProvider::Discord,
        externalAccountId: '777',
        transitions: [],
    ))->toBeEmpty();
});

test('persistMany does nothing for an empty transition list', function (): void {
    resolve(RecordVoicePresence::class)->persistMany([]);

    expect(Voice::query()->count())->toBe(0);
});

test('persistMany records every transition of a move', function (): void {
    ['tenant' => $tenant] = seedPresenceUser('777');

    resolve(RecordVoicePresence::class)->persistMany([
        presenceDto($tenant, '777', VoicePresenceEnum::Left, 'chA'),
        presenceDto($tenant, '777', VoicePresenceEnum::Joined, 'chB'),
    ]);

    expect(Voice::query()->count())->toBe(2)
        ->and(Voice::query()->where('state', 'left')->where('channel_id', 'chA')->exists())->toBeTrue()
        ->and(Voice::query()->where('state', 'joined')->where('channel_id', 'chB')->exists())->toBeTrue();
});

test('persistMany rolls back every row when one transition fails', function (): void {
    ['tenant' => $tenant, 'character' => $character, 'user' => $user, 'identity' => $identity] = seedPresenceUser('555');

    // First transition resolves fine (and inserts), the second blows up — the
    // whole move must roll back, leaving no half-written presence. ResolveUserContext
    // is `final readonly`, so Mockery can't subclass it; bind a plain fake instead.
    $context = UserContext::make(user: $user, character: $character, provider: $identity);
    $fake = new class($context)
    {
        private int $calls = 0;

        public function __construct(private readonly UserContext $context) {}

        public function handle(ResolveUserProviderDTO $dto): UserContext
        {
            $this->calls++;

            if ($this->calls === 1) {
                return $this->context;
            }

            throw new RuntimeException('boom');
        }
    };
    app()->instance(ResolveUserContext::class, $fake);

    expect(fn () => resolve(RecordVoicePresence::class)->persistMany([
        presenceDto($tenant, '555', VoicePresenceEnum::Left, 'chA'),
        presenceDto($tenant, '555', VoicePresenceEnum::Joined, 'chB'),
    ]))->toThrow(RuntimeException::class, 'boom');

    expect(Voice::query()->count())->toBe(0);
});
