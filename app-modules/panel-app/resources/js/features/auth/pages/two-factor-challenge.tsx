import { Head, useForm } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/wayfinder/routes/two-factor/login';
import { TextField } from '@/components/text-field';
import { AuthCard } from '@app/components/auth-card';

const CODE_LENGTH = 6;

const MODES = {
    code: {
        title: 'Authentication code',
        description: 'Enter the code from your authenticator app.',
        toggle: 'Use a recovery code',
    },
    recovery: {
        title: 'Recovery code',
        description: 'Enter one of your emergency recovery codes.',
        toggle: 'Use an authentication code',
    },
} as const;

export default function TwoFactorChallenge() {
    const [mode, setMode] = useState<keyof typeof MODES>('code');
    const form = useForm({ code: '', recovery_code: '' });
    const copy = MODES[mode];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => (mode === 'code' ? { code: data.code } : { recovery_code: data.recovery_code }));
        form.submit(store(), { onError: () => form.reset() });
    };

    const toggleMode = () => {
        setMode(mode === 'code' ? 'recovery' : 'code');
        form.reset();
        form.clearErrors();
    };

    return (
        <>
            <Head title="Two-factor authentication" />
            <AuthCard title={copy.title} description={copy.description}>
                <form onSubmit={submit} noValidate>
                    <FieldGroup>
                        {mode === 'code' ? (
                            <Field data-invalid={!!form.errors.code || undefined} className="items-center">
                                <InputOTP
                                    name="code"
                                    maxLength={CODE_LENGTH}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    autoFocus
                                    disabled={form.processing}
                                    value={form.data.code}
                                    onChange={(value) => form.setData('code', value)}
                                >
                                    <InputOTPGroup>
                                        {Array.from({ length: CODE_LENGTH }, (_, index) => (
                                            <InputOTPSlot key={index} index={index} />
                                        ))}
                                    </InputOTPGroup>
                                </InputOTP>
                                <FieldError className="text-center">{form.errors.code}</FieldError>
                            </Field>
                        ) : (
                            <TextField
                                id="recovery_code"
                                label="Recovery code"
                                autoComplete="one-time-code"
                                autoFocus
                                required
                                value={form.data.recovery_code}
                                error={form.errors.recovery_code}
                                onValueChange={(value) => form.setData('recovery_code', value)}
                            />
                        )}
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Continue
                        </Button>
                        <Button type="button" variant="link" onClick={toggleMode}>
                            {copy.toggle}
                        </Button>
                    </FieldGroup>
                </form>
            </AuthCard>
        </>
    );
}
