import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/wayfinder/routes';
import { send } from '@/wayfinder/routes/verification';
import { AuthCard } from '@app/components/auth-card';

const LINK_SENT = 'verification-link-sent';

export default function VerifyEmail({ status }: { status: string | null }) {
    const form = useForm({});
    const logoutForm = useForm({});

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(send());
    };

    return (
        <>
            <Head title="Email verification" />
            <AuthCard
                title="Verify your email"
                description="Open the link we just emailed you to verify your address."
                status={status === LINK_SENT ? 'A new verification link was sent to your email address.' : null}
            >
                <form onSubmit={submit} className="flex flex-col gap-3">
                    <Button type="submit" variant="secondary" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Resend verification email
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => logoutForm.submit(logout())}
                        disabled={logoutForm.processing}
                    >
                        {logoutForm.processing && <Spinner />}
                        Log out
                    </Button>
                </form>
            </AuthCard>
        </>
    );
}
