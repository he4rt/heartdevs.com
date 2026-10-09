import { useCallback, useReducer } from 'react';
import type { StreamEventDto } from '../feed';

export type FooterMode = 'idle' | 'alert';

export interface FooterBarState {
    mode: FooterMode;
    queue: StreamEventDto[];
    current: StreamEventDto | null;
}

export type FooterBarAction = { type: 'streamEvent'; event: StreamEventDto } | { type: 'alertEnded' };

export const initialFooterBarState: FooterBarState = {
    mode: 'idle',
    queue: [],
    current: null,
};

function playNext(state: FooterBarState): FooterBarState {
    if (state.queue.length === 0) {
        return { mode: 'idle', queue: [], current: null };
    }
    const [next, ...rest] = state.queue;
    return { mode: 'alert', queue: rest, current: next };
}

export function footerBarReducer(state: FooterBarState, action: FooterBarAction): FooterBarState {
    switch (action.type) {
        case 'streamEvent': {
            const queued: FooterBarState = {
                ...state,
                queue: [...state.queue, action.event],
            };
            return queued.mode === 'idle' ? playNext(queued) : queued;
        }
        case 'alertEnded': {
            return playNext(state);
        }
        default:
            return state;
    }
}

export interface FooterBar {
    state: FooterBarState;
    pushEvent: (event: StreamEventDto) => void;
    endAlert: () => void;
}

export function useFooterBar(): FooterBar {
    const [state, dispatch] = useReducer(footerBarReducer, initialFooterBarState);

    const pushEvent = useCallback((event: StreamEventDto) => {
        dispatch({ type: 'streamEvent', event });
    }, []);

    const endAlert = useCallback(() => {
        dispatch({ type: 'alertEnded' });
    }, []);

    return { state, pushEvent, endAlert };
}
