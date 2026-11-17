import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/wayfinder/routes';
import { SetupDialog, useTwoFactorSetup } from '@/components/two-factor-panel';
import { AuthCard } from '@app/components/auth-card';

export default function TwoFactorSetup() {
    const { setup, start, processing, close } = useTwoFactorSetup();
    const logoutForm = useForm({});

    return (
        <>
            <Head title="Set up two factor authentication" />
            <AuthCard
                title="Set up two factor authentication"
                description="Your account needs a code from an authenticator app before you continue."
                footer={
                    <button
                        type="button"
                        className="underline underline-offset-4 hover:text-foreground disabled:opacity-50"
                        onClick={() => logoutForm.submit(logout())}
                        disabled={logoutForm.processing}
                    >
                        Log out
                    </button>
                }
            >
                <Button onClick={start} disabled={processing}>
                    {processing ? <Spinner /> : <ShieldCheck />}
                    Set up two factor
                </Button>
            </AuthCard>
            {setup && <SetupDialog setup={setup} onClose={close} />}
        </>
    );
}
