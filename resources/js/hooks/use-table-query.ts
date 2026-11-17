import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { parseTableQuery, serializeTableQuery, updateTableQuery, type TableQuery } from '@/lib/table-query';

const SEARCH_DEBOUNCE_MS = 300;

/**
 * The URL holds the table state. A change reloads only the listed props, so the layout and the
 * filter options are not sent again.
 */
export function useTableQuery(serverQuery: Record<string, unknown>, defaultPerPage: number, only: string[]) {
    const [state, setState] = useState<TableQuery>(() => parseTableQuery(serverQuery, defaultPerPage));
    const [loading, setLoading] = useState(false);
    const stateRef = useRef(state);
    const searchTimer = useRef<number | undefined>(undefined);

    const visit = (next: TableQuery) => {
        router.get(window.location.pathname, serializeTableQuery(next, defaultPerPage) as Record<string, string>, {
            only: [...only, 'query'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    const update = (change: Partial<TableQuery>) => {
        const next = updateTableQuery(stateRef.current, change);

        stateRef.current = next;
        setState(next);
        window.clearTimeout(searchTimer.current);

        if ('search' in change) {
            searchTimer.current = window.setTimeout(() => visit(next), SEARCH_DEBOUNCE_MS);

            return;
        }

        visit(next);
    };

    useEffect(() => () => window.clearTimeout(searchTimer.current), []);

    return { state, update, loading };
}
