export type ChatStyle = 'lines' | 'bubbles' | 'panel' | 'spotlight' | 'terminal' | 'glass';
export type ChatDirection = 'newest_bottom' | 'newest_top';
export type ChatAlignment = 'left' | 'right';

export interface ChatConfig {
    style: ChatStyle;
    fadeAfterSeconds: number;
    fontSize: number;
    width: number;
    direction: ChatDirection;
    alignment: ChatAlignment;
}

type RawChatValues = Record<string, string | number | null | undefined>;

const STYLES: readonly ChatStyle[] = ['lines', 'bubbles', 'panel', 'spotlight', 'terminal', 'glass'];
const DIRECTIONS: readonly ChatDirection[] = ['newest_bottom', 'newest_top'];
const ALIGNMENTS: readonly ChatAlignment[] = ['left', 'right'];

export const DEFAULT_CHAT_CONFIG: ChatConfig = {
    style: 'lines',
    fadeAfterSeconds: 0,
    fontSize: 24,
    width: 480,
    direction: 'newest_bottom',
    alignment: 'left',
};

const NAME_PALETTE = ['#c9a4ff', '#1ed760', '#ffcb05', '#7dd3fc', '#f9a8d4', '#fdba74', '#a7f3d0', '#fca5a5'];
const MIN_NAME_BRIGHTNESS = 140;
const MAX_BRIGHTNESS = 255;

function oneOf<T extends string>(value: unknown, options: readonly T[], fallback: T): T {
    return options.find((option) => option === value) ?? fallback;
}

function numberOf(value: unknown, fallback: number): number {
    const parsed = typeof value === 'string' && value !== '' ? Number(value) : value;

    return typeof parsed === 'number' && Number.isFinite(parsed) ? parsed : fallback;
}

export function chatConfigFrom(values: RawChatValues): ChatConfig {
    return {
        style: oneOf(values.style, STYLES, DEFAULT_CHAT_CONFIG.style),
        fadeAfterSeconds: numberOf(values.fade_after_seconds, DEFAULT_CHAT_CONFIG.fadeAfterSeconds),
        fontSize: numberOf(values.font_size, DEFAULT_CHAT_CONFIG.fontSize),
        width: numberOf(values.width, DEFAULT_CHAT_CONFIG.width),
        direction: oneOf(values.direction, DIRECTIONS, DEFAULT_CHAT_CONFIG.direction),
        alignment: oneOf(values.alignment, ALIGNMENTS, DEFAULT_CHAT_CONFIG.alignment),
    };
}

function rgbOf(color: string): [number, number, number] | null {
    const match = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(color);

    return match ? [parseInt(match[1], 16), parseInt(match[2], 16), parseInt(match[3], 16)] : null;
}

function brightnessOf([red, green, blue]: [number, number, number]): number {
    return (red * 299 + green * 587 + blue * 114) / 1000;
}

function hashOf(text: string): number {
    let hash = 7;

    for (const char of text) hash = (hash * 31 + char.charCodeAt(0)) >>> 0;

    return hash;
}

export function readableNameColor(color: string, login: string): string {
    const rgb = rgbOf(color);

    if (!rgb) return NAME_PALETTE[hashOf(login.toLowerCase()) % NAME_PALETTE.length];

    const brightness = brightnessOf(rgb);

    if (brightness >= MIN_NAME_BRIGHTNESS) return color;

    const towardWhite = (MIN_NAME_BRIGHTNESS - brightness) / (MAX_BRIGHTNESS - brightness);

    return `#${rgb
        .map((channel) => Math.round(channel + (MAX_BRIGHTNESS - channel) * towardWhite))
        .map((channel) => channel.toString(16).padStart(2, '0'))
        .join('')}`;
}

export function clipToFit(frame: HTMLElement, shell: HTMLElement, list: HTMLElement): void {
    const lines = Array.from(list.children).filter((child): child is HTMLElement => child instanceof HTMLElement);

    for (const line of lines) line.hidden = false;

    const available = frame.clientHeight - (shell.offsetHeight - list.offsetHeight);
    const gap = Number.parseFloat(getComputedStyle(list).rowGap) || 0;
    let used = 0;
    let fitting = 0;

    for (let index = lines.length - 1; index >= 0; index--) {
        const needed = lines[index].offsetHeight + (fitting > 0 ? gap : 0);

        if (used + needed > available) break;

        used += needed;
        fitting += 1;
    }

    for (const line of lines.slice(0, lines.length - fitting)) line.hidden = true;
}
