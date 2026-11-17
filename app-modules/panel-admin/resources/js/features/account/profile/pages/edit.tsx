import { Head, useForm } from '@inertiajs/react';
import { ExternalLink, Monitor } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
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
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { PasskeysPanel } from '@/components/passkeys-panel';
import { TwoFactorPanel } from '@/components/two-factor-panel';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { edit, password, preferences, update } from '@/wayfinder/routes/namespaced/panel-admin/profile';
import { destroy as destroyOtherSessions } from '@/wayfinder/routes/namespaced/panel-admin/profile/sessions';
import { edit as appSecurity } from '@/wayfinder/routes/namespaced/panel-app/settings/security';

type Profile = He4rt.PanelAdmin.Features.Account.Profile.Data.ProfileData;
type BrowserSession = He4rt.PanelAdmin.Features.Account.Profile.Data.BrowserSessionData;

type Props = {
    profile: Profile;
    languages: App.Data.Support.EnumOptionData[];
    sessions: BrowserSession[];
    twoFactorAvailable: boolean;
    passkeysAvailable: boolean;
    twoFactorEnabled: boolean;
    passkeys: He4rt.PanelAdmin.Features.Account.Profile.Data.PasskeyData[];
};

const DEFAULT_ACCENT = '#6b3baa';

function Section({
    title,
    description,
    children,
    footer,
    onSubmit,
}: {
    title: string;
    description: string;
    children: ReactNode;
    footer: ReactNode;
    onSubmit: (event: FormEvent) => void;
}) {
    return (
        <form onSubmit={onSubmit} noValidate>
            <Card>
                <CardHeader>
                    <CardTitle>{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <CardContent>{children}</CardContent>
                <CardFooter className="justify-end gap-2">{footer}</CardFooter>
            </Card>
        </form>
    );
}

function SubmitButton({ processing, children }: { processing: boolean; children: ReactNode }) {
    return (
        <Button type="submit" disabled={processing}>
            {processing && <Spinner />}
            {children}
        </Button>
    );
}

function AccountSection({ profile }: { profile: Profile }) {
    const form = useForm({ name: profile.name, email: profile.email });

    return (
        <Section
            title="Account"
            description="Your name and the email you sign in with."
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(update(), { preserveScroll: true });
            }}
            footer={<SubmitButton processing={form.processing}>Save</SubmitButton>}
        >
            <FieldGroup className="grid gap-6 sm:grid-cols-2">
                <Field data-invalid={!!form.errors.name || undefined}>
                    <FieldLabel htmlFor="name">Name</FieldLabel>
                    <Input
                        id="name"
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
            </FieldGroup>
        </Section>
    );
}

function PasswordSection() {
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    return (
        <Section
            title="Password"
            description="A new password signs out your other sessions."
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(password(), { preserveScroll: true, onSuccess: () => form.reset() });
            }}
            footer={<SubmitButton processing={form.processing}>Update password</SubmitButton>}
        >
            <FieldGroup className="grid gap-6 sm:grid-cols-3">
                <Field data-invalid={!!form.errors.current_password || undefined}>
                    <FieldLabel htmlFor="current_password">Current password</FieldLabel>
                    <Input
                        id="current_password"
                        type="password"
                        autoComplete="current-password"
                        value={form.data.current_password}
                        aria-invalid={!!form.errors.current_password || undefined}
                        onChange={(event) => form.setData('current_password', event.target.value)}
                    />
                    <FieldError>{form.errors.current_password}</FieldError>
                </Field>
                <Field data-invalid={!!form.errors.password || undefined}>
                    <FieldLabel htmlFor="password">New password</FieldLabel>
                    <Input
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        aria-invalid={!!form.errors.password || undefined}
                        onChange={(event) => form.setData('password', event.target.value)}
                    />
                    <FieldError>{form.errors.password}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor="password_confirmation">Confirm new password</FieldLabel>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                    />
                </Field>
            </FieldGroup>
        </Section>
    );
}

function InfoCard({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

/**
 * The admin login takes only an email and a password, so the other ways to log in are managed in the app.
 */
function LoginMethodsCard({ hasPassword }: { hasPassword: boolean }) {
    return (
        <InfoCard
            title="Connected accounts"
            description={
                hasPassword
                    ? 'Connect GitHub, Google, Discord or Twitch to log in to the app with them.'
                    : 'You have no password. Set one in the app to log in to the admin.'
            }
        >
            {/* A plain link: the app is another bundle, so the visit reloads the page. */}
            <Button variant="outline" nativeButton={false} render={<a href={appSecurity().url} />}>
                <ExternalLink /> Open security in the app
            </Button>
        </InfoCard>
    );
}

function PreferencesSection({ profile, languages }: { profile: Profile; languages: Props['languages'] }) {
    const form = useForm({ locale: profile.locale, theme_color: profile.themeColor ?? '' });

    return (
        <Section
            title="Preferences"
            description="Language of the admin and its accent color."
            onSubmit={(event) => {
                event.preventDefault();
                form.submit(preferences(), { preserveScroll: true });
            }}
            footer={<SubmitButton processing={form.processing}>Save preferences</SubmitButton>}
        >
            <FieldGroup className="grid gap-6 sm:grid-cols-2">
                <Field data-invalid={!!form.errors.locale || undefined}>
                    <FieldLabel htmlFor="locale">Language</FieldLabel>
                    <NativeSelect
                        id="locale"
                        value={form.data.locale}
                        onChange={(event) => form.setData('locale', event.target.value)}
                    >
                        {languages.map((language) => (
                            <NativeSelectOption key={language.value} value={language.value}>
                                {language.label}
                            </NativeSelectOption>
                        ))}
                    </NativeSelect>
                    <FieldError>{form.errors.locale}</FieldError>
                </Field>
                <Field data-invalid={!!form.errors.theme_color || undefined}>
                    <FieldLabel htmlFor="theme_color">Accent color</FieldLabel>
                    <div className="flex items-center gap-2">
                        <Input
                            id="theme_color"
                            type="color"
                            className="w-14 p-1"
                            value={form.data.theme_color || DEFAULT_ACCENT}
                            onChange={(event) => form.setData('theme_color', event.target.value)}
                        />
                        <span className="font-mono text-sm text-muted-foreground">
                            {form.data.theme_color || 'Theme default'}
                        </span>
                        {form.data.theme_color && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => form.setData('theme_color', '')}
                            >
                                Reset
                            </Button>
                        )}
                    </div>
                    {!form.errors.theme_color && <FieldDescription>Applies after you save.</FieldDescription>}
                    <FieldError>{form.errors.theme_color}</FieldError>
                </Field>
            </FieldGroup>
        </Section>
    );
}

function SessionsSection({ sessions }: { sessions: BrowserSession[] }) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({ password: '' });
    const hasOtherSessions = sessions.some((session) => !session.isCurrent);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(destroyOtherSessions(), {
            preserveScroll: true,
            onSuccess: () => {
                setConfirming(false);
                form.reset();
            },
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Browser sessions</CardTitle>
                <CardDescription>Devices signed in to your account.</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                {sessions.length === 0 && <p className="text-sm text-muted-foreground">No sessions to show.</p>}
                {sessions.map((session, position) => (
                    <div key={position} className="flex items-center gap-3 text-sm">
                        <Monitor className="size-5 text-muted-foreground" />
                        <div className="flex flex-col">
                            <span className="font-medium">
                                {session.browser} · {session.platform}
                            </span>
                            <span className="text-xs text-muted-foreground">
                                {session.ipAddress ?? 'Unknown IP'} ·{' '}
                                {session.isCurrent ? 'This device' : formatDate(session.lastActiveAt)}
                            </span>
                        </div>
                        {session.isCurrent && (
                            <Badge variant="secondary" className="ml-auto">
                                Current
                            </Badge>
                        )}
                    </div>
                ))}
            </CardContent>
            <CardFooter className="justify-end">
                <Button variant="outline" disabled={!hasOtherSessions} onClick={() => setConfirming(true)}>
                    Log out other sessions
                </Button>
            </CardFooter>
            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                        <DialogHeader>
                            <DialogTitle>Log out other sessions?</DialogTitle>
                            <DialogDescription>Type your password to confirm.</DialogDescription>
                        </DialogHeader>
                        <Field data-invalid={!!form.errors.password || undefined}>
                            <FieldLabel htmlFor="session_password">Password</FieldLabel>
                            <Input
                                id="session_password"
                                type="password"
                                autoFocus
                                autoComplete="current-password"
                                value={form.data.password}
                                aria-invalid={!!form.errors.password || undefined}
                                onChange={(event) => form.setData('password', event.target.value)}
                            />
                            <FieldError>{form.errors.password}</FieldError>
                        </Field>
                        <DialogFooter>
                            <DialogClose render={<Button variant="outline" type="button" />}>Cancel</DialogClose>
                            <SubmitButton processing={form.processing}>Log out other sessions</SubmitButton>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    );
}

export default function EditProfile({
    profile,
    languages,
    sessions,
    twoFactorAvailable,
    passkeysAvailable,
    twoFactorEnabled,
    passkeys,
}: Props) {
    return (
        <>
            <Head title="Profile" />
            <PageHeader heading="Profile" description="Your account, how you log in and your preferences." />
            <div className="flex max-w-3xl flex-col gap-6">
                <AccountSection profile={profile} />
                {profile.hasPassword && <PasswordSection />}
                <LoginMethodsCard hasPassword={profile.hasPassword} />
                {twoFactorAvailable && (
                    <InfoCard
                        title="Two factor authentication"
                        description="Ask for a code from your authenticator app when you log in."
                    >
                        <TwoFactorPanel enabled={twoFactorEnabled} />
                    </InfoCard>
                )}
                {passkeysAvailable && (
                    <InfoCard title="Passkeys" description="Log in to the app with your device instead of a password.">
                        <PasskeysPanel passkeys={passkeys} pageUrl={edit().url} />
                    </InfoCard>
                )}
                <PreferencesSection profile={profile} languages={languages} />
                <SessionsSection sessions={sessions} />
            </div>
        </>
    );
}
