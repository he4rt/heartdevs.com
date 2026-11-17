import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDate } from '@/lib/format';
import type { Page } from '@/lib/page';
import { create, show } from '@/wayfinder/routes/namespaced/panel-admin/teams';
import { HelpLink } from '@admin/components/help-link';

type Team = He4rt.PanelAdmin.Features.Setup.Teams.Data.TeamRowData;
type Status = App.Data.Support.EnumOptionData;

type Props = {
    teams: Page<Team>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    statuses: Status[];
    can: { create: boolean };
};

const ONLY = ['teams'];
const DEFAULT_PER_PAGE = 25;
const TRASH_OPTIONS = [
    { value: '', label: 'Without trashed' },
    { value: 'with', label: 'With trashed' },
    { value: 'only', label: 'Only trashed' },
];

const column = createDataTableColumns<Team>();

export default function TeamsIndex({ teams, query, perPageOptions, statuses, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const setFilter = (key: string, value: string) =>
        table.update({ filters: { ...table.state.filters, [key]: value } });

    const columns = column.columns([
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: ({ row: { original: team } }) => (
                <Link href={show(team.id).url} prefetch className="flex flex-col hover:underline">
                    <span className="flex items-center gap-2 font-medium">
                        {team.name}
                        {team.deletedAt && <Badge variant="destructive">Trashed</Badge>}
                    </span>
                    <span className="text-xs text-muted-foreground">{team.slug}</span>
                </Link>
            ),
        }),
        column.accessor('status', {
            header: 'Status',
            cell: ({ getValue }) => <ToneBadge tone={getValue().tone}>{getValue().label}</ToneBadge>,
        }),
        column.accessor('ownerName', { header: 'Owner' }),
        column.accessor('membersCount', {
            id: 'members_count',
            header: 'Members',
            enableSorting: true,
            cell: ({ getValue }) => <span className="tabular-nums">{getValue()}</span>,
        }),
        column.accessor('createdAt', {
            id: 'created_at',
            header: 'Created',
            enableSorting: true,
            cell: ({ getValue }) => <span className="data-id">{formatDate(getValue())}</span>,
        }),
    ]);

    return (
        <>
            <Head title="Teams" />
            <PageHeader
                heading="Teams"
                description="Groups of users that share data."
                actions={
                    <>
                        <HelpLink path="users/teams" />
                        {can.create && (
                            <Link href={create().url} prefetch className={buttonVariants()}>
                                <Plus /> New team
                            </Link>
                        )}
                    </>
                }
            />
            <DataTable
                columns={columns}
                page={teams}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading}
                perPageOptions={perPageOptions}
                getRowId={(team) => team.id}
                searchPlaceholder="Search by name or slug..."
                emptyMessage="No teams found"
                toolbar={
                    <>
                        <NativeSelect
                            aria-label="Status"
                            value={table.state.filters.status ?? ''}
                            onChange={(event) => setFilter('status', event.target.value)}
                        >
                            <NativeSelectOption value="">Any status</NativeSelectOption>
                            {statuses.map((status) => (
                                <NativeSelectOption key={status.value} value={status.value}>
                                    {status.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Trashed"
                            value={table.state.filters.trashed ?? ''}
                            onChange={(event) => setFilter('trashed', event.target.value)}
                        >
                            {TRASH_OPTIONS.map((option) => (
                                <NativeSelectOption key={option.value} value={option.value}>
                                    {option.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                    </>
                }
            />
        </>
    );
}
