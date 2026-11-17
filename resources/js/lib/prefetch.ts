import { router } from '@inertiajs/react';

/**
 * A prefetched page keeps its props for 30 seconds, and a write can change any of them. So every write that
 * finishes empties the prefetch cache, and the next click shows the new state.
 */
export function flushPrefetchAfterWrites(): void {
    router.on('finish', ({ detail: { visit } }) => {
        const wroteData = visit.method !== 'get' && visit.completed;

        if (wroteData) {
            router.flushAll();
        }
    });
}
