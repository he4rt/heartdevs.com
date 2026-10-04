import type {
    ChatMessageDto,
    FeedEventDto,
    LevelProgressDto,
    StreamEventDto,
    VoiceMemberDto,
    VoiceRosterDto,
} from '../feed';

type Listener = (dto: FeedEventDto) => void;

const CHAT_INTERVAL_MS = 3500;
const EVENT_INTERVAL_MS = 15000;
const SPEAKING_INTERVAL_MS = 2500;
const XP_PER_EVENT = 120;
const XP_PER_MESSAGE = 15;

const CHATTERS = [
    { username: 'devlucasdev', color: '#c9a4ff' },
    { username: 'mariacoda', color: '#1ed760' },
    { username: 'pedroterminal', color: '#ffcb05' },
    { username: 'anabackend', color: '#7dd3fc' },
    { username: 'joaoemcodigo', color: '#f9a8d4' },
];

const MESSAGES = [
    'boa noite, chat!',
    'qual extensão é essa no editor?',
    'esse refactor ficou lindo',
    'primeira vez aqui, já dei follow',
    'bora de testes antes do commit',
    'salve da comunidade He4rt',
    'o Pest com --parallel voa',
    'alguém tem o link do Discord?',
];

const STREAM_EVENTS: StreamEventDto[] = [
    { kind: 'streamEvent', type: 'follow', username: 'devlucasdev' },
    { kind: 'streamEvent', type: 'sub', username: 'mariacoda', tier: 'tier1', months: 3 },
    { kind: 'streamEvent', type: 'cheer', username: 'pedroterminal', bits: 500, message: 'café pro streamer' },
    { kind: 'streamEvent', type: 'giftSub', username: 'anabackend', tier: 'tier1', total: 5 },
    { kind: 'streamEvent', type: 'raid', fromChannel: 'canaldoze', viewers: 42 },
];

const VOICE_MEMBERS: VoiceMemberDto[] = [
    {
        userId: '1',
        displayName: 'Daniel',
        avatarUrl: null,
        speaking: true,
        selfMute: false,
        selfDeaf: false,
        serverMute: false,
        serverDeaf: false,
    },
    {
        userId: '2',
        displayName: 'Maria',
        avatarUrl: null,
        speaking: false,
        selfMute: false,
        selfDeaf: false,
        serverMute: false,
        serverDeaf: false,
    },
    {
        userId: '3',
        displayName: 'Pedro',
        avatarUrl: null,
        speaking: false,
        selfMute: true,
        selfDeaf: false,
        serverMute: false,
        serverDeaf: false,
    },
];

export function subscribeDemoFeed(listener: Listener): () => void {
    let messageCount = 0;
    let eventCount = 0;
    let speakerIndex = 0;
    let level: LevelProgressDto = { kind: 'levelProgress', level: 7, xp: 340, xpToNext: 1000 };

    const gainXp = (amount: number) => {
        const xp = level.xp + amount;
        const leveledUp = xp >= level.xpToNext;
        level = leveledUp ? { ...level, level: level.level + 1, xp: xp - level.xpToNext } : { ...level, xp };
        listener(level);
    };

    const voiceRoster = (): VoiceRosterDto => ({
        kind: 'voiceRoster',
        channelId: 'demo-voice',
        channelName: 'Coworking',
        members: VOICE_MEMBERS.map((member, index) => ({
            ...member,
            speaking: index === speakerIndex && !member.selfMute,
        })),
    });

    const chatMessage = (): ChatMessageDto => {
        const chatter = CHATTERS[messageCount % CHATTERS.length];
        const text = MESSAGES[messageCount % MESSAGES.length];
        messageCount += 1;

        return {
            kind: 'chatMessage',
            msgId: `demo-${messageCount}`,
            username: chatter.username,
            color: chatter.color,
            channel: 'demo',
            badges: [],
            fragments: [{ kind: 'text', text }],
        };
    };

    listener(level);
    listener(voiceRoster());
    listener({
        kind: 'nowPlaying',
        title: 'Lo-fi para codar',
        artist: 'He4rt Radio',
        album: 'Coworking Sessions',
        artUrl: null,
        status: 'playing',
    });

    const timers = [
        setInterval(() => {
            listener(chatMessage());
            gainXp(XP_PER_MESSAGE);
        }, CHAT_INTERVAL_MS),
        setInterval(() => {
            listener(STREAM_EVENTS[eventCount % STREAM_EVENTS.length]);
            eventCount += 1;
            gainXp(XP_PER_EVENT);
        }, EVENT_INTERVAL_MS),
        setInterval(() => {
            speakerIndex = (speakerIndex + 1) % VOICE_MEMBERS.length;
            listener(voiceRoster());
        }, SPEAKING_INTERVAL_MS),
    ];

    return () => timers.forEach(clearInterval);
}
