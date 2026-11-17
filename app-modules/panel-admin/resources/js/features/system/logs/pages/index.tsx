import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, ScrollText } from 'lucide-react';
import { type FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/wayfinder/routes/namespaced/panel-admin/logs';
import { HelpLink } from '@admin/components/help-link';

type Row = He4rt.PanelAdmin.Features.System.Logs.Data.EventRowData;
type Option = App.Data.Support.EnumOptionData;

type Filters = { search?: string; kind?: string; outcome?: string; min_ms?: string };
type Query = { day?: string; older_than?: string; filter?: Filters };

type Props = {
    day: string | null;
    events: Row[];
    olderThan: number | null;
    days: string[];
    query: Query;
    kinds: Option[];
    outcomes: Option[];
};

const ONLY = ['day', 'events', 'olderThan', 'query'];

function withoutBlanks(query: Query): Query {
    const filter = Object.fromEntries(Object.entries(query.filter ?? {}).filter(([, value]) => value)) as Filters;

    return {
        ...(query.day ? { day: query.day } : {}),
        ...(query.older_than ? { older_than: query.older_than } : {}),
        ...(Object.keys(filter).length > 0 ? { filter } : {}),
    };
}

function visit(query: Query) {
    router.get(index().url, withoutBlanks(query), { only: ONLY, preserveState: true, preserveScroll: true });
}

function SearchForm({ filters, onSearch }: { filters: Filters; onSearch: (filters: Filters) => void }) {
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        onSearch({ ...filters, search: String(data.get('search') ?? ''), min_ms: String(data.get('min_ms') ?? '') });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
            <Input
                id="logs-search"
                name="search"
                defaultValue={filters.search ?? ''}
                placeholder="Request id, route, job or command"
                aria-label="Search"
                className="w-72 max-w-full text-base md:text-sm"
            />
            <Input
                id="logs-min-ms"
                name="min_ms"
                type="number"
                min={0}
                defaultValue={filters.min_ms ?? ''}
                placeholder="Slower than (ms)"
                aria-label="Slower than, in milliseconds"
                className="w-40 text-base tabular-nums md:text-sm"
            />
            <Button type="submit" variant="outline">
                Search
            </Button>
        </form>
    );
}

export default function LogsIndex({ day, events, olderThan, days, query, kinds, outcomes }: Props) {
    const filters = query.filter ?? {};
    const setFilters = (next: Filters) => visit({ day: query.day, filter: next });

    return (
        <>
            <Head title="Logs" />
            <PageHeader
                heading="Logs"
                description="One event for each request, job and command that the sampling kept: every error, every slow one, and a sample of the rest."
                actions={<HelpLink path="system/logs" />}
            />

            {days.length === 0 ? (
                <Empty className="rounded-lg border py-12">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <ScrollText />
                        </EmptyMedia>
                        <EmptyTitle>No events yet</EmptyTitle>
                        <EmptyDescription>
                            Events show here as soon as the app writes the first one to storage/logs/events-*.jsonl.
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <div className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <NativeSelect
                            aria-label="Day"
                            value={day ?? ''}
                            onChange={(event) => visit({ day: event.target.value, filter: filters })}
                        >
                            {days.map((option) => (
                                <NativeSelectOption key={option} value={option}>
                                    {option}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Kind"
                            value={filters.kind ?? ''}
                            onChange={(event) => setFilters({ ...filters, kind: event.target.value })}
                        >
                            <NativeSelectOption value="">Any kind</NativeSelectOption>
                            {kinds.map((kind) => (
                                <NativeSelectOption key={kind.value} value={kind.value}>
                                    {kind.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Outcome"
                            value={filters.outcome ?? ''}
                            onChange={(event) => setFilters({ ...filters, outcome: event.target.value })}
                        >
                            <NativeSelectOption value="">Any outcome</NativeSelectOption>
                            {outcomes.map((outcome) => (
                                <NativeSelectOption key={outcome.value} value={outcome.value}>
                                    {outcome.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <SearchForm key={JSON.stringify(filters)} filters={filters} onSearch={setFilters} />
                    </div>

                    <div className="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-4">Time</TableHead>
                                    <TableHead>Kind</TableHead>
                                    <TableHead>What</TableHead>
                                    <TableHead>Outcome</TableHead>
                                    <TableHead className="text-right">Duration</TableHead>
                                    <TableHead className="w-10 pr-4">
                                        <span className="sr-only">Open</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {events.length === 0 ? (
                                    <TableRow className="hover:bg-transparent">
                                        <TableCell colSpan={6} className="py-10 text-center text-muted-foreground">
                                            {olderThan === null
                                                ? 'No events match these filters on this day.'
                                                : 'No events match in the part of the file read so far.'}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    events.map((event) => (
                                        <TableRow key={event.id}>
                                            <TableCell className="pl-4">
                                                <span className="data-id whitespace-nowrap">
                                                    {formatDateTime(event.timestamp)}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <ToneBadge tone={event.kind.tone}>{event.kind.label}</ToneBadge>
                                            </TableCell>
                                            <TableCell className="max-w-md">
                                                <span className="block truncate font-mono text-xs" title={event.title}>
                                                    {event.title}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <span className="flex items-center gap-2 whitespace-nowrap">
                                                    <ToneBadge tone={event.outcome.tone}>
                                                        {event.outcome.label}
                                                    </ToneBadge>
                                                    {event.status !== null && (
                                                        <span className="text-xs text-muted-foreground tabular-nums">
                                                            {event.status}
                                                        </span>
                                                    )}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                {event.durationMs === null ? '—' : `${Math.round(event.durationMs)} ms`}
                                            </TableCell>
                                            <TableCell className="pr-4">
                                                <Link
                                                    href={show({ day: event.day, offset: event.offset }).url}
                                                    prefetch
                                                    aria-label={`Open ${event.title}`}
                                                    className={buttonVariants({ variant: 'ghost', size: 'icon-sm' })}
                                                >
                                                    <ChevronRight />
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {olderThan !== null && (
                        <div className="flex justify-end">
                            <Link
                                href={index().url}
                                data={withoutBlanks({ ...query, older_than: String(olderThan) })}
                                only={ONLY}
                                preserveState
                                preserveScroll
                                className={buttonVariants({ variant: 'outline' })}
                            >
                                {events.length === 0 ? 'Keep searching' : 'Older events'}
                            </Link>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}
