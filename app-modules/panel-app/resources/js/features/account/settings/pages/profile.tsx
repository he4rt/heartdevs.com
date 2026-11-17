import { Head, Link, useForm } from '@inertiajs/react';
import { Monitor } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { confirm as confirmPassword } from '@/wayfinder/routes/password';
import { send } from '@/wayfinder/routes/verification';
import { destroy, update } from '@/wayfinder/routes/namespaced/panel-app/settings/profile';
import { destroy as destroyOtherSessions } from '@/wayfinder/routes/namespaced/panel-app/settings/sessions';
import { TextField } from '@/components/text-field';
import { useAccount } from '@app/hooks/use-account';
import { SettingsLayout, SettingsSection } from '../components/settings-layout';

const LINK_SENT = 'verification-link-sent';

type BrowserSession = He4rt.PanelApp.Features.Account.Settings.Data.BrowserSessionData;

type Confirmation = { hasPassword: boolean; confirmedRecently: boolean };

/**
 * The list is empty unless sessions live in the database, so the button stays off with other drivers.
 */
function BrowserSessions({ sessions, hasPassword, confirmedRecently }: Confirmation & { sessions: BrowserSession[] }) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({ password: '' });
    const hasOtherSessions = sessions.some((session) => !session.isCurrent);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(destroyOtherSessions(), {
            preserveScroll: true,
            onSuccess: () => setConfirming(false),
            onFinish: () => form.reset(),
        });
    };

    return (
        <SettingsSection title="Browser sessions" description="Devices that are logged in to your account.">
            {sessions.length === 0 ? (
                <p className="text-sm text-muted-foreground">No sessions to show.</p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {sessions.map((session, position) => (
                        <li key={position} className="flex items-center gap-3 p-4 text-sm">
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
                        </li>
                    ))}
                </ul>
            )}
            <div>
                <Button variant="outline" disabled={!hasOtherSessions} onClick={() => setConfirming(true)}>
                    Log out other sessions
                </Button>
            </div>
            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                        <DialogHeader>
                            <DialogTitle>Log out other sessions?</DialogTitle>
                            <DialogDescription>
                                Every device except this one is logged out.{' '}
                                {hasPassword && 'Type your password to confirm.'}
                                {!hasPassword && !confirmedRecently && 'Confirm it is you first.'}
                            </DialogDescription>
                        </DialogHeader>
                        {hasPassword && (
                            <TextField
                                id="session_password"
                                label="Password"
                                type="password"
                                autoComplete="current-password"
                                autoFocus
                                value={form.data.password}
                                error={form.errors.password}
                                onValueChange={(value) => form.setData('password', value)}
                            />
                        )}
                        <DialogFooter>
                            <DialogClose render={<Button variant="outline" type="button" />}>Cancel</DialogClose>
                            {hasPassword || confirmedRecently ? (
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing && <Spinner />}
                                    Log out other sessions
                                </Button>
                            ) : (
                                <Button nativeButton={false} render={<Link href={confirmPassword()} />}>
                                    Confirm it is you
                                </Button>
                            )}
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </SettingsSection>
    );
}

function ProfileForm({ status }: { status: string | null }) {
    const account = useAccount();
    const form = useForm({ name: account?.user.name ?? '', email: account?.user.email ?? '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(update(), { preserveScroll: true });
    };

    return (
        <SettingsSection title="Profile" description="Your name and the email you log in with.">
            <form onSubmit={submit} noValidate>
                <FieldGroup>
                    <TextField
                        id="name"
                        label="Name"
                        autoComplete="name"
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
                    {account && !account.user.emailVerified && (
                        <Alert>
                            <AlertDescription>
                                <span>
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        preserveScroll
                                        className="text-foreground underline underline-offset-4"
                                    >
                                        Send the verification email again.
                                    </Link>
                                </span>
                                {status === LINK_SENT && (
                                    <span className="block font-medium text-success">
                                        A new verification link was sent to your email address.
                                    </span>
                                )}
                            </AlertDescription>
                        </Alert>
                    )}
                    <div>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Save
                        </Button>
                    </div>
                </FieldGroup>
            </form>
        </SettingsSection>
    );
}

/**
 * A user without a password confirms with a provider or a passkey first, then deletes without typing anything.
 */
function DeleteAccount({ hasPassword, confirmedRecently }: Confirmation) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(destroy(), {
            preserveScroll: true,
            onSuccess: () => setConfirming(false),
            onFinish: () => form.reset(),
        });
    };

    return (
        <SettingsSection title="Delete account" description="Delete your account and all of its resources.">
            <Alert variant="destructive" className="flex flex-col items-start gap-3">
                <AlertDescription>This cannot be undone. Proceed with caution.</AlertDescription>
                <Button variant="destructive" onClick={() => setConfirming(true)}>
                    Delete account
                </Button>
            </Alert>
            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                        <DialogHeader>
                            <DialogTitle>Delete your account?</DialogTitle>
                            <DialogDescription>
                                Your account and its data are removed for good.{' '}
                                {hasPassword && 'Type your password to confirm.'}
                                {!hasPassword && !confirmedRecently && 'Confirm it is you first.'}
                            </DialogDescription>
                        </DialogHeader>
                        {hasPassword && (
                            <TextField
                                id="delete_password"
                                label="Password"
                                type="password"
                                autoComplete="current-password"
                                autoFocus
                                value={form.data.password}
                                error={form.errors.password}
                                onValueChange={(value) => form.setData('password', value)}
                            />
                        )}
                        <DialogFooter>
                            <DialogClose render={<Button variant="outline" type="button" />}>Cancel</DialogClose>
                            {hasPassword || confirmedRecently ? (
                                <Button type="submit" variant="destructive" disabled={form.processing}>
                                    {form.processing && <Spinner />}
                                    Delete account
                                </Button>
                            ) : (
                                <Button nativeButton={false} render={<Link href={confirmPassword()} />}>
                                    Confirm it is you
                                </Button>
                            )}
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </SettingsSection>
    );
}

type Props = Confirmation & {
    status: string | null;
    sessions: BrowserSession[];
};

export default function Profile({ status, hasPassword, confirmedRecently, sessions }: Props) {
    return (
        <>
            <Head title="Profile settings" />
            <SettingsLayout>
                <ProfileForm status={status} />
                <BrowserSessions sessions={sessions} hasPassword={hasPassword} confirmedRecently={confirmedRecently} />
                <DeleteAccount hasPassword={hasPassword} confirmedRecently={confirmedRecently} />
            </SettingsLayout>
        </>
    );
}
