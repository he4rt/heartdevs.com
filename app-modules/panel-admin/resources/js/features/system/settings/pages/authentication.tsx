import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { CodeBlockCopyButton } from '@/components/ui/code-block';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
} from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { update } from '@/wayfinder/routes/namespaced/panel-admin/settings/authentication';
import { HelpLink } from '@admin/components/help-link';
import { SettingsTabs } from '../components/settings-tabs';

type Props = {
    tabs: He4rt.PanelAdmin.Features.System.Settings.Data.SettingsTabData[];
    settings: He4rt.PanelAdmin.Features.System.Settings.Data.AuthenticationSettingsData;
    socialProviders: He4rt.PanelAdmin.Features.System.Settings.Data.SocialProviderOptionData[];
    canUpdate: boolean;
};

type MethodKey = 'twoFactorEnabled' | 'passkeysEnabled';

const METHODS: { key: MethodKey; label: string; description: string }[] = [
    {
        key: 'twoFactorEnabled',
        label: 'Two factor authentication',
        description:
            'Every user must set up an authenticator app. Users without one set it up on the next page they open.',
    },
    {
        key: 'passkeysEnabled',
        label: 'Passkeys',
        description: 'Users can log in with a passkey and add passkeys on their security page.',
    },
];

export default function AuthenticationSettings({ tabs, settings, socialProviders, canUpdate }: Props) {
    const form = useForm({ ...settings });
    const turnsTwoFactorOn = form.data.twoFactorEnabled && !settings.twoFactorEnabled;
    const socialProviderError = Object.entries(form.errors).find(([key]) => key.startsWith('socialProviders'))?.[1];

    const toggleSocialProvider = (value: string, checked: boolean) =>
        form.setData(
            'socialProviders',
            checked
                ? [...form.data.socialProviders, value]
                : form.data.socialProviders.filter((provider) => provider !== value),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(update(), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Authentication settings" />
            <PageHeader
                heading="Settings"
                description="Control how He4rt works for every user."
                actions={<HelpLink path="system/settings" />}
            />
            <div className="flex flex-col gap-6">
                <SettingsTabs tabs={tabs} />
                <form onSubmit={submit} noValidate className="max-w-2xl">
                    <Card>
                        <CardContent>
                            <FieldGroup>
                                {METHODS.map((method) => (
                                    <Field key={method.key} orientation="horizontal">
                                        <FieldContent>
                                            <FieldLabel htmlFor={method.key}>{method.label}</FieldLabel>
                                            <FieldDescription>{method.description}</FieldDescription>
                                        </FieldContent>
                                        <Switch
                                            id={method.key}
                                            checked={form.data[method.key]}
                                            disabled={!canUpdate}
                                            onCheckedChange={(checked) => form.setData(method.key, checked)}
                                        />
                                    </Field>
                                ))}
                                {turnsTwoFactorOn && (
                                    <Alert>
                                        <AlertDescription>
                                            After you save, every user without two factor, you too, sets it up before
                                            the next page.
                                        </AlertDescription>
                                    </Alert>
                                )}
                                <FieldSeparator />
                                <FieldSet>
                                    <FieldLegend>Social login</FieldLegend>
                                    <FieldDescription>
                                        Users can log in with these providers. New users can sign up with them while
                                        registration is open.
                                    </FieldDescription>
                                    <FieldGroup>
                                        {socialProviders.map((provider) => (
                                            <Field key={provider.value} orientation="horizontal">
                                                <FieldContent>
                                                    <FieldLabel htmlFor={`social-${provider.value}`}>
                                                        {provider.label}
                                                    </FieldLabel>
                                                    <FieldDescription>
                                                        {provider.configured ? (
                                                            'Ready'
                                                        ) : (
                                                            <>
                                                                Add{' '}
                                                                <span className="data-id">
                                                                    {provider.credentialKeys}
                                                                </span>{' '}
                                                                to .env.
                                                            </>
                                                        )}
                                                    </FieldDescription>
                                                    <div className="flex min-w-0 items-center gap-1 text-sm text-muted-foreground">
                                                        <span className="shrink-0">Callback URL</span>
                                                        <span className="data-id truncate">{provider.callbackUrl}</span>
                                                        <CodeBlockCopyButton
                                                            value={provider.callbackUrl}
                                                            aria-label={`Copy the ${provider.label} callback URL`}
                                                        />
                                                    </div>
                                                </FieldContent>
                                                <Switch
                                                    id={`social-${provider.value}`}
                                                    checked={form.data.socialProviders.includes(provider.value)}
                                                    disabled={!canUpdate || !provider.configured}
                                                    onCheckedChange={(checked) =>
                                                        toggleSocialProvider(provider.value, checked)
                                                    }
                                                />
                                            </Field>
                                        ))}
                                    </FieldGroup>
                                    {socialProviderError && <FieldError>{socialProviderError}</FieldError>}
                                    <FieldDescription>
                                        Turning a provider off locks nobody out: users without a password can still use
                                        Forgot password.
                                    </FieldDescription>
                                </FieldSet>
                            </FieldGroup>
                        </CardContent>
                        {canUpdate && (
                            <CardFooter className="justify-end">
                                <Button type="submit" disabled={form.processing || !form.isDirty}>
                                    {form.processing && <Spinner />}
                                    Save
                                </Button>
                            </CardFooter>
                        )}
                    </Card>
                </form>
            </div>
        </>
    );
}
