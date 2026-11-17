import { Head } from '@inertiajs/react';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { Switch } from '@/components/ui/switch';
import { useTableQuery } from '@/hooks/use-table-query';
import type { Page } from '@/lib/page';
import { destroy, store } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags/users';
import { EnabledFilter } from '../components/enabled-filter';
import { FeatureFlagHeader } from '../components/feature-flag-header';
import { useEnabledToggle } from '../hooks/use-enabled-toggle';

type Flag = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagData;
type Row = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagUserRowData;

type Props = {
    flag: Flag;
    users: Page<Row>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    can: { update: boolean };
};

const ONLY = ['users', 'flag'];
const DEFAULT_PER_PAGE = 25;

const column = createDataTableColumns<Row>();

export default function FeatureFlagUsers({ flag, users, query, perPageOptions, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const { toggle, isPending } = useEnabledToggle<Row>('users');

    const columns = column.columns([
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: ({ getValue }) => <span className="font-medium">{getValue()}</span>,
        }),
        column.accessor('email', {
            header: 'Email',
            enableSorting: true,
            cell: ({ getValue }) => <span className="data-id">{getValue()}</span>,
        }),
        column.accessor('enabled', {
            header: 'Enabled',
            meta: { className: 'w-28' },
            cell: ({ row: { original: user } }) => (
                <Switch
                    checked={user.enabled}
                    disabled={!can.update || isPending(user.id)}
                    onCheckedChange={() =>
                        toggle(user, (user.enabled ? destroy : store)({ flag: flag.key, user: user.id }))
                    }
                    aria-label={`${flag.label} for ${user.name}`}
                />
            ),
        }),
    ]);

    return (
        <>
            <Head title={`${flag.label} · Users`} />
            <FeatureFlagHeader flag={flag} />
            <DataTable
                columns={columns}
                page={users}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading}
                perPageOptions={perPageOptions}
                getRowId={(user) => user.id}
                searchPlaceholder="Search by name or email..."
                emptyMessage="No users found"
                toolbar={
                    <EnabledFilter
                        value={table.state.filters.enabled ?? ''}
                        onChange={(value) => table.update({ filters: { ...table.state.filters, enabled: value } })}
                    />
                }
            />
        </>
    );
}
