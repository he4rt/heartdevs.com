import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/wayfinder/routes';
import { email } from '@/wayfinder/routes/password';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';
import { TextLink } from '../components/text-link';

export default function ForgotPassword({ status }: { status: string | null }) {
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(email());
    };

    return (
        <>
            <Head title="Forgot password" />
            <AuthCard
                title="Forgot password"
                description="Enter your email to receive a password reset link."
                status={status}
                footer={
                    <span>
                        Or, return to{' '}
                        <TextLink href={login()} prefetch>
                            log in
                        </TextLink>
                    </span>
                }
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup>
                        <TextField
                            id="email"
                            label="Email address"
                            type="email"
                            autoComplete="email"
                            autoFocus
                            required
                            value={form.data.email}
                            error={form.errors.email}
                            onValueChange={(value) => form.setData('email', value)}
                        />
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Email password reset link
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
