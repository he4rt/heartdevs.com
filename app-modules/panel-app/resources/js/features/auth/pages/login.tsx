import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/wayfinder/routes';
import { store } from '@/wayfinder/routes/login';
import { request } from '@/wayfinder/routes/password';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';
import { PasskeySignIn } from '../components/passkey-sign-in';
import { SocialSignIn } from '../components/social-sign-in';
import { TextLink } from '../components/text-link';

type Props = {
    status: string | null;
    canResetPassword: boolean;
    canRegister: boolean;
    canUsePasskeys: boolean;
    socialProviders: App.Data.Support.EnumOptionData[];
};

export default function Login({ status, canResetPassword, canRegister, canUsePasskeys, socialProviders }: Props) {
    const form = useForm({ email: '', password: '', remember: false });
    const socialError = usePage<{ errors: Record<string, string> }>().props.errors.social;
    const hasSocialProviders = socialProviders.length > 0;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(store(), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="Log in" />
            <AuthCard
                title="Log in to your account"
                description="Enter your email and password below to log in."
                status={status}
                footer={
                    canRegister && (
                        <span>
                            Don't have an account?{' '}
                            <TextLink href={register()} prefetch>
                                Sign up
                            </TextLink>
                        </span>
                    )
                }
            >
                {hasSocialProviders && <SocialSignIn providers={socialProviders} error={socialError} />}
                {canUsePasskeys ? (
                    <PasskeySignIn />
                ) : (
                    hasSocialProviders && <FieldSeparator>Or continue with email</FieldSeparator>
                )}
                <form onSubmit={submit} noValidate>
                    <FieldGroup>
                        <TextField
                            id="email"
                            label="Email address"
                            type="email"
                            autoComplete="username webauthn"
                            autoFocus
                            required
                            value={form.data.email}
                            error={form.errors.email}
                            onValueChange={(value) => form.setData('email', value)}
                        />
                        <TextField
                            id="password"
                            label="Password"
                            type="password"
                            autoComplete="current-password"
                            required
                            value={form.data.password}
                            error={form.errors.password}
                            onValueChange={(value) => form.setData('password', value)}
                            labelAction={
                                canResetPassword && (
                                    <TextLink href={request()} prefetch>
                                        Forgot your password?
                                    </TextLink>
                                )
                            }
                        />
                        <Field orientation="horizontal">
                            <Checkbox
                                id="remember"
                                checked={form.data.remember}
                                onCheckedChange={(checked) => form.setData('remember', checked === true)}
                            />
                            <FieldLabel htmlFor="remember" className="font-normal">
                                Remember me
                            </FieldLabel>
                        </Field>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Log in
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
