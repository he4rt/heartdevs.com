import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/wayfinder/routes/password';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';

type Props = {
    email: string;
    token: string;
    passwordRules: string;
};

export default function ResetPassword({ email, token, passwordRules }: Props) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(update(), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title="Reset password" />
            <AuthCard title="Reset password" description="Choose a new password for your account.">
                <form onSubmit={submit} noValidate>
                    <FieldGroup>
                        <TextField
                            id="email"
                            label="Email address"
                            type="email"
                            autoComplete="email"
                            readOnly
                            value={form.data.email}
                            error={form.errors.email}
                            onValueChange={(value) => form.setData('email', value)}
                        />
                        <TextField
                            id="password"
                            label="New password"
                            type="password"
                            autoComplete="new-password"
                            autoFocus
                            required
                            {...{ passwordrules: passwordRules }}
                            value={form.data.password}
                            error={form.errors.password}
                            onValueChange={(value) => form.setData('password', value)}
                        />
                        <TextField
                            id="password_confirmation"
                            label="Confirm new password"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.data.password_confirmation}
                            error={form.errors.password_confirmation}
                            onValueChange={(value) => form.setData('password_confirmation', value)}
                        />
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Reset password
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
