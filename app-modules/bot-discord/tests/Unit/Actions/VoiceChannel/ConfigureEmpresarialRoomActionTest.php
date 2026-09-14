<?php

declare(strict_types=1);

use He4rt\BotDiscord\Actions\VoiceChannel\ConfigureEmpresarialRoomAction;
use He4rt\BotDiscord\DTO\EmpresarialOverwritePlan;
use He4rt\BotDiscord\DTO\VoiceChannelDTO;
use He4rt\BotDiscord\Enums\EmpresarialRejectionReason;

beforeEach(function (): void {
    config()->set('bot-discord.roles.partners', [
        'brd' => 'role-brd',
        'acme' => 'role-acme',
    ]);
});

/**
 * @param  list<string>  $users
 */
function trackedRoom(string $channelId, string $ownerId = 'owner-1', array $users = []): VoiceChannelDTO
{
    return VoiceChannelDTO::make([
        'guildId' => 'guild-1',
        'channelId' => $channelId,
        'ownerId' => $ownerId,
        'usersCount' => count($users),
        'users' => $users,
    ]);
}

it('approves a member privatizing their own /sala room', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'brd',
        callerRoleIds: ['role-brd'],
        currentChannelId: 'room-1',
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->isApproved())->toBeTrue()
        ->and($decision->rejection)->toBeNull()
        ->and($decision->plan)->toBeInstanceOf(EmpresarialOverwritePlan::class)
        ->and($decision->plan->companySlug)->toBe('brd')
        ->and($decision->plan->partnerRoleId)->toBe('role-brd')
        ->and($decision->plan->denyEveryone)->toBe(['view_channel', 'connect', 'speak', 'use_vad', 'send_messages', 'read_message_history'])
        ->and($decision->plan->allowPartnerRole)->toBe(['view_channel', 'connect', 'speak', 'use_vad', 'send_messages', 'read_message_history']);
});

it('approves privatizing a room a teammate created (gate is role, not ownership)', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'brd',
        callerRoleIds: ['role-brd'],
        currentChannelId: 'room-1',
        activeChannels: [trackedRoom('room-1', ownerId: 'someone-else')],
    );

    expect($decision->isApproved())->toBeTrue()
        ->and($decision->plan->partnerRoleId)->toBe('role-brd');
});

it('rejects when the caller does not hold the selected company role', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'acme',
        callerRoleIds: ['role-brd'],
        currentChannelId: 'room-1',
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->isApproved())->toBeFalse()
        ->and($decision->plan)->toBeNull()
        ->and($decision->rejection)->toBe(EmpresarialRejectionReason::MissingPartnerRole);
});

it('rejects when the caller is in a channel that is not /sala-tracked', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'brd',
        callerRoleIds: ['role-brd'],
        currentChannelId: 'permanent-channel',
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->rejection)->toBe(EmpresarialRejectionReason::NotInTrackedRoom);
});

it('rejects when the caller is not connected to any voice channel', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'brd',
        callerRoleIds: ['role-brd'],
        currentChannelId: null,
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->rejection)->toBe(EmpresarialRejectionReason::NotInTrackedRoom);
});

it('rejects when the selected company is not registered', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'ghost-corp',
        callerRoleIds: ['role-brd'],
        currentChannelId: 'room-1',
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->rejection)->toBe(EmpresarialRejectionReason::UnknownCompany);
});

it('allows only the selected company role for a member of several partners', function (): void {
    $decision = new ConfigureEmpresarialRoomAction()->execute(
        companySlug: 'acme',
        callerRoleIds: ['role-brd', 'role-acme'],
        currentChannelId: 'room-1',
        activeChannels: [trackedRoom('room-1')],
    );

    expect($decision->isApproved())->toBeTrue()
        ->and($decision->plan->companySlug)->toBe('acme')
        ->and($decision->plan->partnerRoleId)->toBe('role-acme');
});

it('is a harmless re-stamp when run again on an already-configured room', function (): void {
    $action = new ConfigureEmpresarialRoomAction();
    $args = [
        'companySlug' => 'brd',
        'callerRoleIds' => ['role-brd'],
        'currentChannelId' => 'room-1',
        'activeChannels' => [trackedRoom('room-1')],
    ];

    $first = $action->execute(...$args);
    $second = $action->execute(...$args);

    expect($first->plan->partnerRoleId)->toBe($second->plan->partnerRoleId)
        ->and($first->plan->denyEveryone)->toBe($second->plan->denyEveryone)
        ->and($first->plan->allowPartnerRole)->toBe($second->plan->allowPartnerRole);
});
