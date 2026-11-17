import { Head, router, useForm, usePage } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { FieldError, FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { edit as profile } from '@/wayfinder/routes/namespaced/panel-app/settings/profile';
import { redirect as confirmWith } from '@/wayfinder/routes/namespaced/panel-app/social-confirmation';
import { confirm, confirmOptions } from '@/wayfinder/routes/passkey';
import { store } from '@/wayfinder/routes/password/confirm';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';

type Props = {
    hasPassword: boolean;
    socialProviders: App.Data.Support.EnumOptionData[];
    canUsePasskeys: boolean;
};

function PasswordConfirmation() {
    const form = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(store(), { onFinish: () => form.reset('password') });
    };

    return (
        <form onSubmit={submit} noValidate>
            <FieldGroup>
                <TextField
                    id="password"
                    label="Password"
                    type="password"
                    autoComplete="current-password"
                    autoFocus
                    required
                    value={form.data.password}
                    error={form.errors.password}
                    onValueChange={(value) => form.setData('password', value)}
                />
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Confirm password
                </Button>
            </FieldGroup>
        </form>
    );
}

function PasskeyConfirmation() {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        routes: { options: confirmOptions().url, submit: confirm().url },
        onSuccess: (response) => router.visit(response.redirect ?? profile().url),
    });

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <Button type="button" variant="outline" onClick={verify} disabled={isLoading}>
                {isLoading ? <Spinner /> : <KeyRound />}
                {isLoading ? 'Waiting for your passkey...' : 'Confirm with a passkey'}
            </Button>
            {error && <FieldError className="text-center">{error}</FieldError>}
        </>
    );
}

/**
 * A user who signed up with a provider has no password, so they confirm with that provider or a passkey.
 */
export default function ConfirmPassword({ hasPassword, socialProviders, canUsePasskeys }: Props) {
    const socialError = usePage<{ errors: Record<string, string> }>().props.errors.social;

    return (
        <>
            <Head title="Confirm password" />
            <AuthCard
                title={hasPassword ? 'Confirm your password' : 'Confirm it is you'}
                description="This is a secure area of the application. Please confirm it is you before you continue."
            >
                {hasPassword ? (
                    <PasswordConfirmation />
                ) : (
                    <div className="flex flex-col gap-2">
                        {socialProviders.map((provider) => (
                            <Button
                                key={provider.value}
                                variant="outline"
                                nativeButton={false}
                                render={<a href={confirmWith(provider.value).url} />}
                            >
                                Confirm with {provider.label}
                            </Button>
                        ))}
                        {canUsePasskeys && <PasskeyConfirmation />}
                        {socialError && <FieldError className="text-center">{socialError}</FieldError>}
                    </div>
                )}
            </AuthCard>
        </>
    );
}
