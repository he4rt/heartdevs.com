import { Head, Link, useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button, buttonVariants } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/wayfinder/routes/namespaced/panel-admin/roles';
import { PermissionMatrix } from '../components/permission-matrix';

type Props = {
    role: He4rt.PanelAdmin.Features.Setup.Roles.Data.RoleFormData | null;
    matrix: He4rt.PanelAdmin.Features.Setup.Roles.Data.PermissionMatrixData;
    can: { update: boolean };
};

export default function RoleForm({ role, matrix, can }: Props) {
    const form = useForm({ name: role?.name ?? '', permissions: role?.permissionIds ?? [] });
    const readOnly = !can.update;
    const permissionError = Object.entries(form.errors).find(([key]) => key.startsWith('permissions'))?.[1];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(role ? update(role.id) : store(), { preserveScroll: true });
    };

    return (
        <>
            <Head title={role ? `Role ${role.name}` : 'New role'} />
            <form onSubmit={submit} noValidate>
                <PageHeader
                    heading={role ? `Edit role: ${role.name}` : 'New role'}
                    description="A role groups permissions. Users get every permission of their roles."
                    actions={
                        <>
                            <Link href={index().url} prefetch className={buttonVariants({ variant: 'outline' })}>
                                {readOnly ? 'Back' : 'Cancel'}
                            </Link>
                            {!readOnly && (
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <Spinner />}
                                    {role ? 'Save changes' : 'Create role'}
                                </Button>
                            )}
                        </>
                    }
                />
                <div className="flex flex-col gap-6">
                    {role?.isSystem && (
                        <Alert>
                            <Lock />
                            <AlertTitle>System role</AlertTitle>
                            <AlertDescription>
                                config/rbac.php defines the permissions of this role, and sync:permissions writes them.
                                Change the config file instead.
                            </AlertDescription>
                        </Alert>
                    )}
                    <Field data-invalid={!!form.errors.name || undefined} className="max-w-sm">
                        <FieldLabel htmlFor="name">Name</FieldLabel>
                        <Input
                            id="name"
                            autoFocus={!role}
                            disabled={readOnly}
                            value={form.data.name}
                            aria-invalid={!!form.errors.name || undefined}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        <FieldError>{form.errors.name}</FieldError>
                    </Field>
                    <Field data-invalid={!!permissionError || undefined}>
                        <div className="flex items-baseline justify-between gap-3">
                            <FieldLabel>Permissions</FieldLabel>
                            <span aria-live="polite" className="text-sm text-muted-foreground tabular-nums">
                                {form.data.permissions.length} of {matrix.total} selected
                            </span>
                        </div>
                        <PermissionMatrix
                            matrix={matrix}
                            value={form.data.permissions}
                            readOnly={readOnly}
                            onChange={(permissions) => form.setData('permissions', permissions)}
                        />
                        <FieldError>{permissionError}</FieldError>
                    </Field>
                </div>
            </form>
        </>
    );
}
