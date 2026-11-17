import { Head, Link, router } from '@inertiajs/react';
import { Lock, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDate } from '@/lib/format';
import type { Page } from '@/lib/page';
import { create, destroy, edit } from '@/wayfinder/routes/namespaced/panel-admin/roles';
import { HelpLink } from '@admin/components/help-link';

type Role = He4rt.PanelAdmin.Features.Setup.Roles.Data.RoleRowData;

type Props = {
    roles: Page<Role>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    can: { create: boolean };
};

const ONLY = ['roles'];
const DEFAULT_PER_PAGE = 25;

const column = createDataTableColumns<Role>();

export default function RolesIndex({ roles, query, perPageOptions, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const [deleting, setDeleting] = useState<Role | null>(null);
    const [processing, setProcessing] = useState(false);

    const columns = column.columns([
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: ({ row: { original: role } }) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium">{role.label}</span>
                    {role.isSystem && (
                        <Badge variant="secondary">
                            <Lock /> System
                        </Badge>
                    )}
                </div>
            ),
        }),
        column.accessor('guardName', {
            header: 'Guard',
            cell: ({ getValue }) => (
                <Badge variant="outline" className="font-mono">
                    {getValue()}
                </Badge>
            ),
        }),
        column.accessor('permissionsCount', {
            id: 'permissions_count',
            header: 'Permissions',
            enableSorting: true,
            cell: ({ getValue }) => <span className="tabular-nums">{getValue()}</span>,
        }),
        column.accessor('usersCount', {
            header: 'Users',
            cell: ({ getValue }) => <span className="tabular-nums">{getValue()}</span>,
        }),
        column.accessor('updatedAt', {
            id: 'updated_at',
            header: 'Updated',
            enableSorting: true,
            cell: ({ getValue }) => <span className="data-id">{formatDate(getValue())}</span>,
        }),
        column.display({
            id: 'actions',
            meta: { className: 'text-right' },
            cell: ({ row: { original: role } }) => (
                <div className="flex justify-end gap-1">
                    <Link
                        href={edit(role.id).url}
                        prefetch
                        className={buttonVariants({ variant: 'ghost', size: 'sm' })}
                    >
                        <Pencil /> {role.isSystem ? 'View' : 'Edit'}
                    </Link>
                    {role.isSystem ? (
                        <Tooltip>
                            <TooltipTrigger render={<span tabIndex={0} />}>
                                <Button variant="ghost" size="icon-sm" disabled aria-label={`Delete ${role.label}`}>
                                    <Trash2 />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>System roles cannot be deleted</TooltipContent>
                        </Tooltip>
                    ) : (
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            aria-label={`Delete ${role.label}`}
                            onClick={() => setDeleting(role)}
                        >
                            <Trash2 />
                        </Button>
                    )}
                </div>
            ),
        }),
    ]);

    return (
        <>
            <Head title="Roles & Permissions" />
            <PageHeader
                heading="Roles & Permissions"
                description="Roles decide what each user can do."
                actions={
                    <>
                        <HelpLink path="users/roles" />
                        {can.create && (
                            <Link href={create().url} prefetch className={buttonVariants()}>
                                <Plus /> New role
                            </Link>
                        )}
                    </>
                }
            />
            <DataTable
                columns={columns}
                page={roles}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading}
                perPageOptions={perPageOptions}
                getRowId={(role) => role.id}
                searchPlaceholder="Search roles..."
                emptyMessage="No roles found"
            />
            {deleting && (
                <ConfirmDialog
                    heading={`Delete ${deleting.label}?`}
                    text={`${deleting.usersCount} ${deleting.usersCount === 1 ? 'user loses' : 'users lose'} the permissions of this role.`}
                    confirmLabel="Yes, delete"
                    destructive
                    processing={processing}
                    onClose={() => setDeleting(null)}
                    onConfirm={() =>
                        router.visit(destroy(deleting.id), {
                            preserveScroll: true,
                            only: ONLY,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                            onSuccess: () => setDeleting(null),
                        })
                    }
                />
            )}
        </>
    );
}
