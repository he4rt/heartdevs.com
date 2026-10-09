export const DEFAULT_AT = '19:05';
export const DEFAULT_TITLE = 'Aulão Semanal da He4rt Developers';

export const DONE_GRACE_MS = 90 * 60_000;

const STORAGE_PREFIX = 'starting-soon:session:';

export interface StartingSoonConfig {
    title: string;
    deadline: number;
    totalMs: number;
}

interface Session {
    deadline: number;
    startedAt: number;
}

type SessionStorage = Pick<Storage, 'getItem' | 'setItem'>;

export function nextWallClock(hhmm: string, now: number): number | null {
    const m = /^(\d{1,2}):(\d{2})$/.exec(hhmm.trim());
    if (!m) return null;
    const [h, min] = [Number(m[1]), Number(m[2])];
    if (h > 23 || min > 59) return null;
    const d = new Date(now);
    d.setHours(h, min, 0, 0);
    const today = d.getTime();
    if (today > now || now - today < DONE_GRACE_MS) return today;
    d.setDate(d.getDate() + 1);
    return d.getTime();
}

export function resolveDeadline(
    params: URLSearchParams,
    now: number,
    storage: SessionStorage | null,
): { deadline: number; totalMs: number } {
    const key = STORAGE_PREFIX + params.toString();

    const minutesParam = Number(params.get('minutes'));
    const useMinutes = !params.has('at') && Number.isFinite(minutesParam) && minutesParam > 0;
    const wall = useMinutes ? null : nextWallClock(params.get('at') ?? DEFAULT_AT, now);
    const target = wall ?? now + minutesParam * 60_000;

    const stored = params.has('fresh') ? null : readSession(storage, key);
    const stillLive = stored !== null && stored.deadline > now;
    const session: Session =
        stillLive && (wall === null || stored.deadline === wall) ? stored : { deadline: target, startedAt: now };

    if (session !== stored) storage?.setItem(key, JSON.stringify(session));
    return { deadline: session.deadline, totalMs: Math.max(1, session.deadline - session.startedAt) };
}

function readSession(storage: SessionStorage | null, key: string): Session | null {
    try {
        const raw = storage?.getItem(key);
        if (!raw) return null;
        const s = JSON.parse(raw) as Partial<Session>;
        if (typeof s.deadline !== 'number' || typeof s.startedAt !== 'number') return null;
        return { deadline: s.deadline, startedAt: s.startedAt };
    } catch {
        return null;
    }
}

export function readStartingSoonConfig(
    search: string = window.location.search,
    now: number = Date.now(),
    storage: SessionStorage | null = safeStorage(),
): StartingSoonConfig {
    const params = new URLSearchParams(search);
    const title = params.get('title')?.trim() || DEFAULT_TITLE;
    return { title, ...resolveDeadline(params, now, storage) };
}

function safeStorage(): Storage | null {
    try {
        return window.localStorage;
    } catch {
        return null;
    }
}

export function splitClock(remainingMs: number): { hh: string | null; mm: string; ss: string } {
    const total = Math.max(0, Math.ceil(remainingMs / 1000));
    const hours = Math.floor(total / 3600);
    const pad = (n: number) => String(n).padStart(2, '0');
    return {
        hh: hours > 0 ? pad(hours) : null,
        mm: pad(Math.floor((total % 3600) / 60)),
        ss: pad(total % 60),
    };
}

export const MAX_RAIL_MS = 60 * 60_000;
const MIN_RAIL_MS = 60_000;

export function configFromServer(
    values: Record<string, string>,
    since: number,
    now: number = Date.now(),
): StartingSoonConfig | null {
    const deadline = nextWallClock(values.at ?? '', now);
    if (deadline === null) return null;

    const elapsed = deadline - since;
    const totalMs = Math.min(MAX_RAIL_MS, Math.max(MIN_RAIL_MS, elapsed));

    return {
        title: values.title?.trim() || DEFAULT_TITLE,
        deadline,
        totalMs,
    };
}

export function withServerValues(
    fallback: StartingSoonConfig,
    values: Record<string, string>,
    since: number,
    now: number = Date.now(),
): StartingSoonConfig {
    const title = values.title?.trim() || fallback.title;
    const scheduled = configFromServer(values, since, now);

    return scheduled ? { ...scheduled, title } : { ...fallback, title };
}
