import { Head } from '@inertiajs/react';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { Switch } from '@/components/ui/switch';
import { useTableQuery } from '@/hooks/use-table-query';
import type { Page } from '@/lib/page';
import { destroy, store } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags/teams';
import { EnabledFilter } from '../components/enabled-filter';
import { FeatureFlagHeader } from '../components/feature-flag-header';
import { useEnabledToggle } from '../hooks/use-enabled-toggle';

type Flag = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagData;
type Row = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagTeamRowData;

type Props = {
    flag: Flag;
    teams: Page<Row>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    can: { update: boolean };
};

const ONLY = ['teams', 'flag'];
const DEFAULT_PER_PAGE = 25;

const column = createDataTableColumns<Row>();

export default function FeatureFlagTeams({ flag, teams, query, perPageOptions, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const { toggle, isPending } = useEnabledToggle<Row>('teams');

    const columns = column.columns([
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: ({ getValue }) => <span className="font-medium">{getValue()}</span>,
        }),
        column.accessor('slug', {
            header: 'Slug',
            cell: ({ getValue }) => <span className="data-id">{getValue()}</span>,
        }),
        column.accessor('enabled', {
            header: 'Enabled',
            meta: { className: 'w-28' },
            cell: ({ row: { original: team } }) => (
                <Switch
                    checked={team.enabled}
                    disabled={!can.update || isPending(team.id)}
                    onCheckedChange={() =>
                        toggle(team, (team.enabled ? destroy : store)({ flag: flag.key, team: team.id }))
                    }
                    aria-label={`${flag.label} for ${team.name}`}
                />
            ),
        }),
    ]);

    return (
        <>
            <Head title={`${flag.label} · Teams`} />
            <FeatureFlagHeader flag={flag} />
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
                    <EnabledFilter
                        value={table.state.filters.enabled ?? ''}
                        onChange={(value) => table.update({ filters: { ...table.state.filters, enabled: value } })}
                    />
                }
            />
        </>
    );
}
