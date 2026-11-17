import { router } from '@inertiajs/react';
import type { UrlMethodPair } from '@inertiajs/core';
import { useState } from 'react';
import type { Page } from '@/lib/page';

type Toggleable = { id: string; enabled: boolean };

/**
 * Flips one row at once and rolls it back when the request fails. A row stays disabled while its request runs.
 */
export function useEnabledToggle<Row extends Toggleable>(prop: string) {
    const [pendingIds, setPendingIds] = useState<string[]>([]);

    const toggle = (row: Row, route: UrlMethodPair) =>
        router
            .optimistic<Record<string, Page<Row>>>((props) => ({
                [prop]: {
                    ...props[prop],
                    data: props[prop].data.map((item) =>
                        item.id === row.id ? { ...item, enabled: !row.enabled } : item,
                    ),
                },
            }))
            .visit(route, {
                preserveScroll: true,
                only: [prop, 'flag'],
                onStart: () => setPendingIds((ids) => [...ids, row.id]),
                onFinish: () => setPendingIds((ids) => ids.filter((id) => id !== row.id)),
            });

    return { toggle, isPending: (id: string) => pendingIds.includes(id) };
}
