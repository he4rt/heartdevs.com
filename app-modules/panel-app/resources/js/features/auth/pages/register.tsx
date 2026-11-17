import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/wayfinder/routes';
import { store } from '@/wayfinder/routes/register';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';
import { TextLink } from '../components/text-link';

export default function Register({ passwordRules }: { passwordRules: string }) {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(store(), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title="Register" />
            <AuthCard
                title="Create an account"
                description="Enter your details below to create your account."
                footer={
                    <span>
                        Already have an account?{' '}
                        <TextLink href={login()} prefetch>
                            Log in
                        </TextLink>
                    </span>
                }
            >
                <form onSubmit={submit} noValidate>
                    <FieldGroup>
                        <TextField
                            id="name"
                            label="Name"
                            autoComplete="name"
                            autoFocus
                            required
                            value={form.data.name}
                            error={form.errors.name}
                            onValueChange={(value) => form.setData('name', value)}
                        />
                        <TextField
                            id="email"
                            label="Email address"
                            type="email"
                            autoComplete="email"
                            required
                            value={form.data.email}
                            error={form.errors.email}
                            onValueChange={(value) => form.setData('email', value)}
                        />
                        <TextField
                            id="password"
                            label="Password"
                            type="password"
                            autoComplete="new-password"
                            required
                            {...{ passwordrules: passwordRules }}
                            value={form.data.password}
                            error={form.errors.password}
                            onValueChange={(value) => form.setData('password', value)}
                        />
                        <TextField
                            id="password_confirmation"
                            label="Confirm password"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.data.password_confirmation}
                            error={form.errors.password_confirmation}
                            onValueChange={(value) => form.setData('password_confirmation', value)}
                        />
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Create account
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
