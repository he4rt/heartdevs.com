export type TableQuery = {
    search: string;
    sort: string | null;
    page: number;
    perPage: number;
    filters: Record<string, string>;
};

function asRecord(value: unknown): Record<string, unknown> {
    return value !== null && typeof value === 'object' && !Array.isArray(value)
        ? (value as Record<string, unknown>)
        : {};
}

function asPositiveInt(value: unknown, fallback: number): number {
    const parsed = Number(value);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

export function parseTableQuery(query: Record<string, unknown>, defaultPerPage: number): TableQuery {
    const { search, ...filters } = asRecord(query.filter);

    return {
        search: typeof search === 'string' ? search : '',
        sort: typeof query.sort === 'string' && query.sort !== '' ? query.sort : null,
        page: asPositiveInt(query.page, 1),
        perPage: asPositiveInt(query.per_page, defaultPerPage),
        filters: Object.fromEntries(
            Object.entries(filters).filter((entry): entry is [string, string] => typeof entry[1] === 'string'),
        ),
    };
}

export function serializeTableQuery(query: TableQuery, defaultPerPage: number): Record<string, unknown> {
    const filter = Object.fromEntries(
        Object.entries({ ...query.filters, search: query.search.trim() }).filter(([, value]) => value !== ''),
    );

    return {
        ...(Object.keys(filter).length > 0 ? { filter } : {}),
        ...(query.sort ? { sort: query.sort } : {}),
        ...(query.page > 1 ? { page: query.page } : {}),
        ...(query.perPage !== defaultPerPage ? { per_page: query.perPage } : {}),
    };
}

export function updateTableQuery(query: TableQuery, change: Partial<TableQuery>): TableQuery {
    const resetsPage = 'search' in change || 'filters' in change || 'sort' in change || 'perPage' in change;

    return { ...query, ...change, page: change.page ?? (resetsPage ? 1 : query.page) };
}

export type ColumnSort = { id: string; desc: boolean };

/**
 * The server reads one sort key, with a leading dash for descending order: "name" or "-name".
 */
export function sortingFromQuery(sort: string | null): ColumnSort[] {
    if (sort === null) {
        return [];
    }

    const desc = sort.startsWith('-');

    return [{ id: desc ? sort.slice(1) : sort, desc }];
}

export function sortFromSorting(sorting: ColumnSort[]): string | null {
    const [first] = sorting;

    if (!first) {
        return null;
    }

    return first.desc ? `-${first.id}` : first.id;
}
