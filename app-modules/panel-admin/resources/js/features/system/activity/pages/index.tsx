import { Head, Link } from '@inertiajs/react';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDateTime } from '@/lib/format';
import type { Page } from '@/lib/page';
import { show } from '@/wayfinder/routes/namespaced/panel-admin/activity';
import { RiskBadge } from '../components/risk-badge';

type Activity = He4rt.PanelAdmin.Features.System.Activity.Data.ActivityRowData;

type Props = {
    activities: Page<Activity>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    filters: { events: string[]; subjectTypes: { value: string; label: string }[] };
};

const ONLY = ['activities'];
const DEFAULT_PER_PAGE = 25;
const RISK_LEVELS = ['low', 'medium', 'high', 'critical'];

const column = createDataTableColumns<Activity>();

const columns = column.columns([
    column.accessor('createdAt', {
        id: 'created_at',
        header: 'When',
        enableSorting: true,
        cell: ({ row: { original: activity } }) => (
            <Link href={show(activity.id).url} prefetch className="data-id whitespace-nowrap hover:underline">
                {formatDateTime(activity.createdAt)}
            </Link>
        ),
    }),
    column.accessor('description', {
        header: 'Activity',
        cell: ({ row: { original: activity } }) => (
            <div className="flex flex-col gap-1">
                <span className="font-medium">{activity.description}</span>
                {activity.event && (
                    <Badge variant="outline" className="font-mono">
                        {activity.event}
                    </Badge>
                )}
            </div>
        ),
    }),
    column.display({
        id: 'subject',
        header: 'Subject',
        cell: ({ row: { original: activity } }) =>
            activity.subjectType ? (
                <div className="flex flex-col">
                    <span>{activity.subjectLabel}</span>
                    <span className="text-xs text-muted-foreground">{activity.subjectType}</span>
                </div>
            ) : (
                '—'
            ),
    }),
    column.accessor('causerName', { header: 'By', cell: ({ getValue }) => getValue() ?? 'System' }),
    column.accessor('riskScore', {
        id: 'risk_score',
        header: 'Risk',
        enableSorting: true,
        cell: ({ row: { original: activity } }) => <RiskBadge level={activity.riskLevel} score={activity.riskScore} />,
    }),
]);

export default function ActivityIndex({ activities, query, perPageOptions, filters }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const setFilter = (key: string, value: string) =>
        table.update({ filters: { ...table.state.filters, [key]: value } });

    return (
        <>
            <Head title="Activity log" />
            <PageHeader
                heading="Activity log"
                description="Every change made in He4rt, with who made it and from where."
            />
            <DataTable
                columns={columns}
                page={activities}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading}
                perPageOptions={perPageOptions}
                getRowId={(activity) => String(activity.id)}
                searchPlaceholder="Search descriptions..."
                emptyMessage="No activity found"
                toolbar={
                    <>
                        <NativeSelect
                            aria-label="Event"
                            value={table.state.filters.event ?? ''}
                            onChange={(event) => setFilter('event', event.target.value)}
                        >
                            <NativeSelectOption value="">Any event</NativeSelectOption>
                            {filters.events.map((event) => (
                                <NativeSelectOption key={event} value={event}>
                                    {event}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Subject"
                            value={table.state.filters.subject_type ?? ''}
                            onChange={(event) => setFilter('subject_type', event.target.value)}
                        >
                            <NativeSelectOption value="">Any subject</NativeSelectOption>
                            {filters.subjectTypes.map((type) => (
                                <NativeSelectOption key={type.value} value={type.value}>
                                    {type.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <NativeSelect
                            aria-label="Risk"
                            value={table.state.filters.risk_level ?? ''}
                            onChange={(event) => setFilter('risk_level', event.target.value)}
                        >
                            <NativeSelectOption value="">Any risk</NativeSelectOption>
                            {RISK_LEVELS.map((level) => (
                                <NativeSelectOption key={level} value={level}>
                                    {level}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                    </>
                }
            />
        </>
    );
}
