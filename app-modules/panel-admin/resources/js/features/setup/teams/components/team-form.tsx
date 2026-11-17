import { useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { RouteDefinition } from '@/wayfinder/wayfinder';
import { UserPicker, type UserOption } from '@admin/components/user-picker';

type Team = He4rt.PanelAdmin.Features.Setup.Teams.Data.TeamDetailData;
type Status = App.Data.Support.EnumOptionData;

type TeamFormProps = {
    team: Team | null;
    statuses: Status[];
    action: RouteDefinition<'post' | 'put'>;
    readOnly?: boolean;
    footer: (processing: boolean) => ReactNode;
};

export function TeamForm({ team, statuses, action, readOnly, footer }: TeamFormProps) {
    const form = useForm({
        name: team?.name ?? '',
        slug: team?.slug ?? '',
        description: team?.description ?? '',
        contact_email: team?.contactEmail ?? '',
        status: team?.status.value ?? statuses[0]?.value ?? '',
        owner_id: team?.owner.id ?? '',
    });
    const [owner, setOwner] = useState<UserOption | null>(team?.owner ?? null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(action, { preserveScroll: true });
    };

    const invalid = (field: keyof typeof form.data) => (form.errors[field] ? true : undefined);

    return (
        <form onSubmit={submit} noValidate className="flex max-w-2xl flex-col gap-6">
            <FieldGroup>
                <div className="grid gap-6 sm:grid-cols-2">
                    <Field data-invalid={invalid('name')}>
                        <FieldLabel htmlFor="name">Name</FieldLabel>
                        <Input
                            id="name"
                            autoFocus={!team}
                            disabled={readOnly}
                            value={form.data.name}
                            aria-invalid={invalid('name')}
                            onChange={(event) => form.setData('name', event.target.value)}
                        />
                        <FieldError>{form.errors.name}</FieldError>
                    </Field>
                    <Field data-invalid={invalid('slug')}>
                        <FieldLabel htmlFor="slug">Slug</FieldLabel>
                        <Input
                            id="slug"
                            disabled={readOnly || team !== null}
                            placeholder="Generated from the name"
                            value={form.data.slug}
                            aria-invalid={invalid('slug')}
                            onChange={(event) => form.setData('slug', event.target.value)}
                        />
                        {!form.errors.slug && (
                            <FieldDescription>
                                {team ? 'The slug cannot change.' : 'Lowercase letters, numbers and dashes.'}
                            </FieldDescription>
                        )}
                        <FieldError>{form.errors.slug}</FieldError>
                    </Field>
                </div>
                <Field data-invalid={invalid('description')}>
                    <FieldLabel htmlFor="description">Description</FieldLabel>
                    <Textarea
                        id="description"
                        rows={3}
                        disabled={readOnly}
                        value={form.data.description}
                        aria-invalid={invalid('description')}
                        onChange={(event) => form.setData('description', event.target.value)}
                    />
                    <FieldError>{form.errors.description}</FieldError>
                </Field>
                <div className="grid gap-6 sm:grid-cols-2">
                    <Field data-invalid={invalid('owner_id')}>
                        <FieldLabel htmlFor="owner">Owner</FieldLabel>
                        <UserPicker
                            id="owner"
                            value={owner}
                            disabled={readOnly}
                            invalid={!!form.errors.owner_id}
                            onSelect={(user) => {
                                setOwner(user);
                                form.setData('owner_id', user.id);
                            }}
                        />
                        {!form.errors.owner_id && <FieldDescription>The owner is always a member.</FieldDescription>}
                        <FieldError>{form.errors.owner_id}</FieldError>
                    </Field>
                    <Field data-invalid={invalid('status')}>
                        <FieldLabel htmlFor="status">Status</FieldLabel>
                        <NativeSelect
                            id="status"
                            disabled={readOnly}
                            value={form.data.status}
                            aria-invalid={invalid('status')}
                            onChange={(event) => form.setData('status', event.target.value)}
                        >
                            {statuses.map((status) => (
                                <NativeSelectOption key={status.value} value={status.value}>
                                    {status.label}
                                </NativeSelectOption>
                            ))}
                        </NativeSelect>
                        <FieldError>{form.errors.status}</FieldError>
                    </Field>
                </div>
                <Field data-invalid={invalid('contact_email')} className="sm:max-w-[calc(50%-0.75rem)]">
                    <FieldLabel htmlFor="contact_email">Contact email</FieldLabel>
                    <Input
                        id="contact_email"
                        type="email"
                        disabled={readOnly}
                        value={form.data.contact_email}
                        aria-invalid={invalid('contact_email')}
                        onChange={(event) => form.setData('contact_email', event.target.value)}
                    />
                    <FieldError>{form.errors.contact_email}</FieldError>
                </Field>
            </FieldGroup>
            {!readOnly && <div className="flex gap-2">{footer(form.processing)}</div>}
        </form>
    );
}
