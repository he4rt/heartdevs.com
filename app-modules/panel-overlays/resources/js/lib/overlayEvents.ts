import type { ChatBadgeDto, ChatMessageDto, OverlayConfigDto, StreamEventDto, SubTierDto } from '../feed';
import type { ChatBadgePayload, ChatMessagePayload, SceneSettings } from '../types';

type TwitchTier = '1000' | '2000' | '3000';

interface AlertActor {
    login: string;
    displayName: string;
}

export type AlertPayload = { actor: AlertActor | null; isTest: boolean } & (
    | { type: 'follow'; details: null }
    | { type: 'sub'; details: { tier: TwitchTier; months: number; message: string | null } }
    | { type: 'gift_sub'; details: { tier: TwitchTier; total: number } }
    | { type: 'cheer'; details: { bits: number; message: string | null } }
    | { type: 'raid'; details: { viewers: number } }
);

export interface SettingsPayload {
    scene: string;
    settings: SceneSettings;
}

const ANONYMOUS = 'anônimo';

const TIERS: Record<TwitchTier, SubTierDto> = {
    '1000': 'tier1',
    '2000': 'tier2',
    '3000': 'tier3',
};

const VALUE_KEYS: Record<string, string> = {
    starts_at: 'at',
};

export function toStreamEvent(alert: AlertPayload): StreamEventDto {
    const username = alert.actor?.login ?? ANONYMOUS;

    switch (alert.type) {
        case 'follow':
            return { kind: 'streamEvent', type: 'follow', username };
        case 'sub':
            return {
                kind: 'streamEvent',
                type: 'sub',
                username,
                tier: TIERS[alert.details.tier],
                months: alert.details.months,
            };
        case 'gift_sub':
            return {
                kind: 'streamEvent',
                type: 'giftSub',
                username,
                tier: TIERS[alert.details.tier],
                total: alert.details.total,
            };
        case 'cheer':
            return {
                kind: 'streamEvent',
                type: 'cheer',
                username,
                bits: alert.details.bits,
                message: alert.details.message ?? '',
            };
        case 'raid':
            return { kind: 'streamEvent', type: 'raid', fromChannel: username, viewers: alert.details.viewers };
    }
}

function hasImage(badge: ChatBadgePayload): badge is ChatBadgeDto {
    return badge.url !== null;
}

export function toChatMessage(payload: ChatMessagePayload): ChatMessageDto {
    return {
        kind: 'chatMessage',
        msgId: payload.msgId,
        chatterId: payload.chatterId,
        username: payload.username,
        color: payload.color ?? '',
        channel: '',
        badges: payload.badges.filter(hasImage),
        fragments: payload.fragments,
    };
}

export function toOverlayConfig(scene: string, settings: SceneSettings, since: number): OverlayConfigDto {
    const values: Record<string, string> = {};

    for (const [key, value] of Object.entries(settings)) {
        if (value) values[VALUE_KEYS[key] ?? key] = value;
    }

    return { kind: 'overlayConfig', overlays: { [scene]: { values, since } } };
}
