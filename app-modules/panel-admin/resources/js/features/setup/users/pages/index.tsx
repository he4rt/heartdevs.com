import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, VenetianMask } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDate } from '@/lib/format';
import type { Page } from '@/lib/page';
import { destroy, impersonate, store, update } from '@/wayfinder/routes/namespaced/panel-admin/users';
import { HelpLink } from '@admin/components/help-link';

type User = He4rt.PanelAdmin.Features.Setup.Users.Data.UserRowData;

type Props = {
    users: Page<User>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    can: { create: boolean };
};

type DialogState = { edit: User | null } | { delete: User } | null;

const ONLY = ['users'];
const DEFAULT_PER_PAGE = 25;

function UserFormDialog({ user, onClose }: { user: User | null; onClose: () => void }) {
    const form = useForm({ name: user?.name ?? '', email: user?.email ?? '', password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(user ? update(user.id) : store(), { preserveScroll: true, only: ONLY, onSuccess: onClose });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                    <DialogHeader>
                        <DialogTitle>{user ? 'Edit user' : 'New user'}</DialogTitle>
                        <DialogDescription>
                            {user ? user.email : 'The user can sign in with this email and password.'}
                        </DialogDescription>
                    </DialogHeader>
                    <FieldGroup>
                        <Field data-invalid={!!form.errors.name || undefined}>
                            <FieldLabel htmlFor="name">Name</FieldLabel>
                            <Input
                                id="name"
                                autoFocus
                                value={form.data.name}
                                aria-invalid={!!form.errors.name || undefined}
                                onChange={(event) => form.setData('name', event.target.value)}
                            />
                            <FieldError>{form.errors.name}</FieldError>
                        </Field>
                        <Field data-invalid={!!form.errors.email || undefined}>
                            <FieldLabel htmlFor="email">Email</FieldLabel>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                aria-invalid={!!form.errors.email || undefined}
                                onChange={(event) => form.setData('email', event.target.value)}
                            />
                            <FieldError>{form.errors.email}</FieldError>
                        </Field>
                        <Field data-invalid={!!form.errors.password || undefined}>
                            <FieldLabel htmlFor="password">Password</FieldLabel>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                aria-invalid={!!form.errors.password || undefined}
                                onChange={(event) => form.setData('password', event.target.value)}
                            />
                            {user && !form.errors.password && (
                                <FieldDescription>Leave empty to keep the current password.</FieldDescription>
                            )}
                            <FieldError>{form.errors.password}</FieldError>
                        </Field>
                    </FieldGroup>
                    <DialogFooter>
                        <DialogClose render={<Button variant="outline" />}>Cancel</DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {user ? 'Save changes' : 'Create user'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

const column = createDataTableColumns<User>();

export default function UsersIndex({ users, query, perPageOptions, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const [dialog, setDialog] = useState<DialogState>(null);
    const [deleting, setDeleting] = useState(false);
    const close = () => setDialog(null);

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
        column.display({
            id: 'roles',
            header: 'Roles',
            cell: ({ row }) => (
                <div className="flex flex-wrap gap-1">
                    {row.original.roles.map((role) => (
                        <Badge
                            key={role}
                            variant={role === 'super_admin' ? 'default' : 'secondary'}
                            className="font-mono"
                        >
                            {role}
                        </Badge>
                    ))}
                </div>
            ),
        }),
        column.display({
            id: 'verified',
            header: 'Verified',
            cell: ({ row }) =>
                row.original.verified ? (
                    <Badge variant="outline">Verified</Badge>
                ) : (
                    <Badge variant="secondary">Pending</Badge>
                ),
        }),
        column.accessor('createdAt', {
            id: 'created_at',
            header: 'Created',
            enableSorting: true,
            cell: ({ getValue }) => <span className="data-id">{formatDate(getValue())}</span>,
        }),
        column.display({
            id: 'actions',
            meta: { className: 'text-right' },
            cell: ({ row: { original: user } }) => (
                <div className="flex justify-end gap-1">
                    {user.canImpersonate && (
                        <Button variant="ghost" size="sm" onClick={() => router.visit(impersonate(user.id))}>
                            <VenetianMask /> Impersonate
                        </Button>
                    )}
                    <Button variant="ghost" size="sm" onClick={() => setDialog({ edit: user })}>
                        <Pencil /> Edit
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label={`Delete ${user.name}`}
                        onClick={() => setDialog({ delete: user })}
                    >
                        <Trash2 />
                    </Button>
                </div>
            ),
        }),
    ]);

    return (
        <>
            <Head title="Users" />
            <PageHeader
                heading="Users"
                description="Accounts that can sign in to He4rt."
                actions={
                    <>
                        <HelpLink path="users/managing-users" />
                        {can.create && (
                            <Button onClick={() => setDialog({ edit: null })}>
                                <Plus /> New user
                            </Button>
                        )}
                    </>
                }
            />
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
            />
            {dialog !== null && 'edit' in dialog && <UserFormDialog user={dialog.edit} onClose={close} />}
            {dialog !== null && 'delete' in dialog && (
                <ConfirmDialog
                    heading={`Delete ${dialog.delete.name}?`}
                    text="The user loses access right away. This cannot be undone."
                    confirmLabel="Yes, delete"
                    destructive
                    processing={deleting}
                    onClose={close}
                    onConfirm={() =>
                        router.visit(destroy(dialog.delete.id), {
                            preserveScroll: true,
                            only: ONLY,
                            onStart: () => setDeleting(true),
                            onFinish: () => setDeleting(false),
                            onSuccess: close,
                        })
                    }
                />
            )}
        </>
    );
}
