import { router } from '@inertiajs/react';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { FieldError, FieldSeparator } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/wayfinder/routes/namespaced/panel-app';

/**
 * Hidden on browsers without WebAuthn, so the email form stays the only way in.
 */
export function PasskeySignIn() {
    const { verify, isLoading, error, isSupported } = usePasskeyVerify({
        onSuccess: (response) => router.visit(response.redirect ?? dashboard().url),
    });

    if (!isSupported) {
        return null;
    }

    return (
        <>
            <div className="flex flex-col gap-2">
                <Button type="button" variant="outline" onClick={verify} disabled={isLoading}>
                    {isLoading ? <Spinner /> : <KeyRound />}
                    {isLoading ? 'Waiting for your passkey...' : 'Sign in with a passkey'}
                </Button>
                {error && <FieldError className="text-center">{error}</FieldError>}
            </div>
            <FieldSeparator>Or continue with email</FieldSeparator>
        </>
    );
}
