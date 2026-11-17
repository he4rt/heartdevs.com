import {
    createColumnHelper,
    FlexRender,
    functionalUpdate,
    metaHelper,
    rowPaginationFeature,
    rowSortingFeature,
    tableFeatures,
    useTable,
    type ColumnDef,
    type Header,
    type PaginationState,
    type SortingState,
    type Updater,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight, Search, SearchX } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { Page } from '@/lib/page';
import { sortFromSorting, sortingFromQuery, type TableQuery } from '@/lib/table-query';
import { cn } from 'cn';

type DataTableColumnMeta = { className?: string };

const features = tableFeatures({
    rowSortingFeature,
    rowPaginationFeature,
    columnMeta: metaHelper<DataTableColumnMeta>(),
});

type DataTableFeatures = typeof features;

export type DataTableColumn<T extends object> = ColumnDef<DataTableFeatures, T, any>;

/**
 * Columns are not sortable by default. A sortable column needs an accessor and `enableSorting: true`,
 * and its `id` is the sort key the server accepts.
 */
export function createDataTableColumns<T extends object>() {
    return createColumnHelper<DataTableFeatures, T>();
}

type DataTableProps<T extends object> = {
    columns: DataTableColumn<T>[];
    page: Page<T>;
    query: TableQuery;
    onQueryChange: (change: Partial<TableQuery>) => void;
    getRowId: (row: T) => string;
    loading?: boolean;
    perPageOptions?: number[];
    searchPlaceholder?: string;
    emptyMessage?: string;
    toolbar?: ReactNode;
};

const SORT_ICONS = { asc: ArrowUp, desc: ArrowDown } as const;
const ARIA_SORT = { asc: 'ascending', desc: 'descending' } as const;

function SortButton<T extends object>({ header }: { header: Header<DataTableFeatures, T> }) {
    const direction = header.column.getIsSorted();
    const Icon = direction ? SORT_ICONS[direction] : ArrowUpDown;

    return (
        <Button variant="ghost" size="sm" className="-ml-2.5" onClick={header.column.getToggleSortingHandler()}>
            <FlexRender header={header} />
            <Icon className={cn(!direction && 'opacity-40')} />
        </Button>
    );
}

export function DataTable<T extends object>({
    columns,
    page,
    query,
    onQueryChange,
    getRowId,
    loading,
    perPageOptions = [10, 25, 50, 100],
    searchPlaceholder = 'Search...',
    emptyMessage = 'No records found',
    toolbar,
}: DataTableProps<T>) {
    const { meta } = page;
    const sorting = sortingFromQuery(query.sort);
    const pagination: PaginationState = { pageIndex: meta.currentPage - 1, pageSize: meta.perPage };

    const table = useTable({
        features,
        columns,
        data: page.data,
        getRowId,
        defaultColumn: { enableSorting: false },
        manualSorting: true,
        manualPagination: true,
        enableMultiSort: false,
        sortDescFirst: false,
        pageCount: meta.lastPage,
        state: { sorting, pagination },
        onSortingChange: (updater: Updater<SortingState>) =>
            onQueryChange({ sort: sortFromSorting(functionalUpdate(updater, sorting)) }),
        onPaginationChange: (updater: Updater<PaginationState>) => {
            const next = functionalUpdate(updater, pagination);
            const pageSizeChanged = next.pageSize !== pagination.pageSize;

            onQueryChange(pageSizeChanged ? { perPage: next.pageSize } : { page: next.pageIndex + 1 });
        },
    });

    const rows = table.getRowModel().rows;
    const columnCount = table.getAllLeafColumns().length;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                <InputGroup className="max-w-sm">
                    <InputGroupInput
                        type="search"
                        aria-label="Search"
                        placeholder={searchPlaceholder}
                        value={query.search}
                        onChange={(event) => onQueryChange({ search: event.target.value })}
                    />
                    <InputGroupAddon>
                        <Search />
                    </InputGroupAddon>
                </InputGroup>
                {toolbar}
            </div>

            <div
                className={cn('overflow-hidden rounded-xl border bg-card transition-opacity', loading && 'opacity-60')}
                aria-busy={loading}
            >
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id} className="hover:bg-transparent">
                                {headerGroup.headers.map((header) => {
                                    const direction = header.column.getIsSorted();

                                    return (
                                        <TableHead
                                            key={header.id}
                                            className={header.column.columnDef.meta?.className}
                                            aria-sort={direction ? ARIA_SORT[direction] : undefined}
                                        >
                                            {header.isPlaceholder ? null : header.column.getCanSort() ? (
                                                <SortButton header={header} />
                                            ) : (
                                                <FlexRender header={header} />
                                            )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {rows.length === 0 && (
                            <TableRow className="hover:bg-transparent">
                                <TableCell colSpan={columnCount} className="p-0">
                                    <Empty className="py-10">
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <SearchX />
                                            </EmptyMedia>
                                            <EmptyTitle>{emptyMessage}</EmptyTitle>
                                        </EmptyHeader>
                                    </Empty>
                                </TableCell>
                            </TableRow>
                        )}
                        {rows.map((row) => (
                            <TableRow key={row.id}>
                                {row.getAllCells().map((cell) => (
                                    <TableCell key={cell.id} className={cell.column.columnDef.meta?.className}>
                                        <FlexRender cell={cell} />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                <span>{meta.total === 0 ? 'No results' : `Showing ${meta.from} to ${meta.to} of ${meta.total}`}</span>
                <div className="flex items-center gap-2">
                    <label className="flex items-center gap-2 whitespace-nowrap">
                        Per page
                        <NativeSelect
                            size="sm"
                            value={meta.perPage}
                            onChange={(event) => table.setPageSize(Number(event.target.value))}
                        >
                            {perPageOptions.map((option) => (
                                <NativeSelectOption key={option} value={option}>
                                    {option}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                    </label>
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Previous page"
                        disabled={!table.getCanPreviousPage()}
                        onClick={() => table.previousPage()}
                    >
                        <ChevronLeft />
                    </Button>
                    <span className="tabular-nums">
                        {meta.currentPage} / {Math.max(meta.lastPage, 1)}
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="Next page"
                        disabled={!table.getCanNextPage()}
                        onClick={() => table.nextPage()}
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </div>
    );
}
