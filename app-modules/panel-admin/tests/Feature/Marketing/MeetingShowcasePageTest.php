<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Marketing\Pages\MeetingShowcasePage;
use Illuminate\Support\Facades\Date;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    config(['app.display_timezone' => 'America/Sao_Paulo']);

    $admin = User::factory()->create();

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('it loads Discord participant data from every supported metadata shape', function (): void {
    $metadataByAccount = [
        '111' => [
            'username' => 'root-user',
            'global_name' => 'Root User',
            'avatar' => 'https://cdn.example.com/root.png',
        ],
        '222' => [
            'user' => [
                'username' => 'profile-user',
                'global_name' => 'Profile User',
                'avatar' => 'profile-avatar',
            ],
        ],
        '333' => [
            'author' => [
                'username' => 'message-user',
                'global_name' => 'Message User',
                'avatar' => 'message-avatar',
            ],
        ],
    ];

    foreach ($metadataByAccount as $accountId => $metadata) {
        $identity = ExternalIdentity::factory()->create([
            'provider' => IdentityProvider::Discord,
            'external_account_id' => $accountId,
            'metadata' => $metadata,
        ]);

        Message::factory()->create([
            'external_identity_id' => $identity->id,
            'channel_id' => 'meeting-channel',
            'sent_at' => Date::parse('2026-08-03 22:30:00', 'America/Sao_Paulo')->utc(),
        ]);
    }

    $linkedUser = User::factory()->create([
        'username' => 'linked-user',
        'name' => 'Linked User',
    ]);

    $identityWithoutMetadata = ExternalIdentity::factory()->create([
        'model_id' => $linkedUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '444',
        'metadata' => [],
    ]);

    Message::factory()->create([
        'external_identity_id' => $identityWithoutMetadata->id,
        'channel_id' => 'meeting-channel',
        'sent_at' => Date::parse('2026-08-03 22:45:00', 'America/Sao_Paulo')->utc(),
    ]);

    $disconnectedIdentity = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '555',
        'metadata' => [
            'author' => [
                'username' => 'disconnected-user',
                'global_name' => 'Disconnected User',
            ],
        ],
    ]);

    Message::factory()->create([
        'external_identity_id' => $disconnectedIdentity->id,
        'channel_id' => 'meeting-channel',
        'sent_at' => Date::parse('2026-08-03 23:00:00', 'America/Sao_Paulo')->utc(),
    ]);

    $disconnectedIdentity->delete();

    $component = livewire(MeetingShowcasePage::class)
        ->set('channelId', 'meeting-channel')
        ->set('startDate', '2026-08-03T22:00')
        ->set('endDate', '2026-08-03T23:40')
        ->call('loadParticipants')
        ->assertSet('loaded', value: true);

    /** @var array<int, array{discord_id: string|null, username: string, global_name: string, avatar_url: string|null, total_messages: int}> $participants */
    $participants = $component->get('participants');
    $participantsByAccount = collect($participants)->keyBy('discord_id');

    expect($participants)->toHaveCount(5)
        ->and($participantsByAccount->get('111'))->toMatchArray([
            'username' => 'root-user',
            'global_name' => 'Root User',
            'avatar_url' => 'https://cdn.example.com/root.png',
        ])
        ->and($participantsByAccount->get('222'))->toMatchArray([
            'username' => 'profile-user',
            'global_name' => 'Profile User',
            'avatar_url' => 'https://cdn.discordapp.com/avatars/222/profile-avatar.png?size=128',
        ])
        ->and($participantsByAccount->get('333'))->toMatchArray([
            'username' => 'message-user',
            'global_name' => 'Message User',
            'avatar_url' => 'https://cdn.discordapp.com/avatars/333/message-avatar.png?size=128',
        ])
        ->and($participantsByAccount->get('444'))->toMatchArray([
            'username' => 'linked-user',
            'global_name' => 'Linked User',
        ])
        ->and($participantsByAccount->get('555'))->toMatchArray([
            'username' => 'disconnected-user',
            'global_name' => 'Disconnected User',
        ]);
});

test('it includes messages sent during the selected end minute', function (): void {
    $identity = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '444',
        'metadata' => ['username' => 'last-minute-user'],
    ]);

    Message::factory()->create([
        'external_identity_id' => $identity->id,
        'channel_id' => 'meeting-channel',
        'sent_at' => Date::parse('2026-08-03 23:40:59', 'America/Sao_Paulo')->utc(),
    ]);

    livewire(MeetingShowcasePage::class)
        ->set('channelId', 'meeting-channel')
        ->set('startDate', '2026-08-03T22:00')
        ->set('endDate', '2026-08-03T23:40')
        ->call('loadParticipants')
        ->assertSet('loaded', value: true)
        ->assertSet('participants.0.username', 'last-minute-user');
});

test('it excludes messages sent after the selected end minute', function (): void {
    $identity = ExternalIdentity::factory()->create([
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '445',
        'metadata' => ['username' => 'too-late-user'],
    ]);

    Message::factory()->create([
        'external_identity_id' => $identity->id,
        'channel_id' => 'meeting-channel',
        'sent_at' => Date::parse('2026-08-03 23:41:00', 'America/Sao_Paulo')->utc(),
    ]);

    livewire(MeetingShowcasePage::class)
        ->set('channelId', 'meeting-channel')
        ->set('startDate', '2026-08-03T22:00')
        ->set('endDate', '2026-08-03T23:40')
        ->call('loadParticipants')
        ->assertSet('loaded', value: true)
        ->assertSet('participants', []);
});
