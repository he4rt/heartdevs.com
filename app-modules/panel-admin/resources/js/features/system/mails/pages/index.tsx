import { Head, Link } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDateTime } from '@/lib/format';
import type { Page } from '@/lib/page';
import { show } from '@/wayfinder/routes/namespaced/panel-admin/mails';
import { MailStatusBadge } from '../components/mail-status-badge';

type Mail = He4rt.PanelAdmin.Features.System.Mails.Data.MailRowData;

type Props = {
    mails: Page<Mail>;
    query: Record<string, unknown>;
    perPageOptions: number[];
};

const ONLY = ['mails'];
const DEFAULT_PER_PAGE = 25;
const STATUSES = ['pending', 'sent', 'delivered', 'bounced'];

const column = createDataTableColumns<Mail>();

const columns = column.columns([
    column.accessor('subject', {
        header: 'Subject',
        enableSorting: true,
        cell: ({ row: { original: mail } }) => (
            <Link href={show(mail.id).url} prefetch className="flex flex-col hover:underline">
                <span className="flex items-center gap-1.5 font-medium">
                    {mail.subject ?? '(no subject)'}
                    {mail.attachmentsCount > 0 && (
                        <Paperclip
                            className="size-3.5 text-muted-foreground"
                            aria-label={`${mail.attachmentsCount} attachments`}
                        />
                    )}
                </span>
                {mail.mailClass && <span className="text-xs text-muted-foreground">{mail.mailClass}</span>}
            </Link>
        ),
    }),
    column.accessor('to', {
        header: 'To',
        cell: ({ getValue }) => <span className="data-id">{getValue().join(', ') || '—'}</span>,
    }),
    column.accessor('status', { header: 'Status', cell: ({ getValue }) => <MailStatusBadge status={getValue()} /> }),
    column.accessor('createdAt', {
        id: 'created_at',
        header: 'Created',
        enableSorting: true,
        cell: ({ getValue }) => <span className="data-id whitespace-nowrap">{formatDateTime(getValue())}</span>,
    }),
]);

export default function MailsIndex({ mails, query, perPageOptions }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);

    return (
        <>
            <Head title="Mails" />
            <PageHeader heading="Mails" description="Every mail the app sent, as it left." />
            <DataTable
                columns={columns}
                page={mails}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading}
                perPageOptions={perPageOptions}
                getRowId={(mail) => String(mail.id)}
                searchPlaceholder="Search subject or recipient..."
                emptyMessage="No mails found"
                toolbar={
                    <NativeSelect
                        aria-label="Status"
                        value={table.state.filters.status ?? ''}
                        onChange={(event) =>
                            table.update({ filters: { ...table.state.filters, status: event.target.value } })
                        }
                    >
                        <NativeSelectOption value="">Any status</NativeSelectOption>
                        {STATUSES.map((status) => (
                            <NativeSelectOption key={status} value={status}>
                                {status}
                            </NativeSelectOption>
                        ))}
                    </NativeSelect>
                }
            />
        </>
    );
}
