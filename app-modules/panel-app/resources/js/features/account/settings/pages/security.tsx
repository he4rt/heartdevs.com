import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/wayfinder/routes/namespaced/panel-app/settings/password';
import { edit as security } from '@/wayfinder/routes/namespaced/panel-app/settings/security';
import { TextField } from '@/components/text-field';
import { PasskeysPanel } from '@/components/passkeys-panel';
import { SettingsLayout, SettingsSection } from '../components/settings-layout';
import { SocialConnectionsPanel } from '../components/social-connections-panel';
import { TwoFactorPanel } from '@/components/two-factor-panel';

type Props = {
    passwordRules: string;
    twoFactorAvailable: boolean;
    passkeysAvailable: boolean;
    twoFactorEnabled: boolean;
    passkeys: He4rt.PanelApp.Features.Account.Settings.Data.PasskeyData[];
    hasPassword: boolean;
    socialConnections: He4rt.PanelApp.Features.Account.Settings.Data.SocialConnectionData[];
};

/**
 * A user who signed up with a provider has no current password, so the form sets the first one.
 */
function PasswordForm({ passwordRules, hasPassword }: { passwordRules: string; hasPassword: boolean }) {
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(hasPassword ? update() : store(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <SettingsSection
            title={hasPassword ? 'Password' : 'Set a password'}
            description={
                hasPassword
                    ? 'Use a long, random password to keep your account safe.'
                    : 'You log in with a provider. Add a password to log in with your email too.'
            }
        >
            <form onSubmit={submit} noValidate>
                <FieldGroup>
                    {hasPassword && (
                        <TextField
                            id="current_password"
                            label="Current password"
                            type="password"
                            autoComplete="current-password"
                            value={form.data.current_password}
                            error={form.errors.current_password}
                            onValueChange={(value) => form.setData('current_password', value)}
                        />
                    )}
                    <TextField
                        id="password"
                        label="New password"
                        type="password"
                        autoComplete="new-password"
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
                        value={form.data.password_confirmation}
                        error={form.errors.password_confirmation}
                        onValueChange={(value) => form.setData('password_confirmation', value)}
                    />
                    <div>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Save password
                        </Button>
                    </div>
                </FieldGroup>
            </form>
        </SettingsSection>
    );
}

export default function Security({
    passwordRules,
    twoFactorAvailable,
    passkeysAvailable,
    twoFactorEnabled,
    passkeys,
    hasPassword,
    socialConnections,
}: Props) {
    return (
        <>
            <Head title="Security settings" />
            <SettingsLayout>
                <PasswordForm passwordRules={passwordRules} hasPassword={hasPassword} />
                {socialConnections.length > 0 && (
                    <SettingsSection title="Connected accounts" description="Log in with an account you already have.">
                        <SocialConnectionsPanel connections={socialConnections} />
                    </SettingsSection>
                )}
                {twoFactorAvailable && (
                    <SettingsSection
                        title="Two factor authentication"
                        description="Protect your account with a code from an authenticator app."
                    >
                        <TwoFactorPanel enabled={twoFactorEnabled} />
                    </SettingsSection>
                )}
                {passkeysAvailable && (
                    <SettingsSection title="Passkeys" description="Log in with your device instead of a password.">
                        <PasskeysPanel passkeys={passkeys} pageUrl={security().url} />
                    </SettingsSection>
                )}
            </SettingsLayout>
        </>
    );
}
