import { router, useForm, useHttp } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { Eye, EyeOff, RefreshCw, ShieldBan, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldError } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import {
    confirm,
    disable,
    enable,
    qrCode,
    recoveryCodes,
    regenerateRecoveryCodes,
    secretKey,
} from '@/wayfinder/routes/two-factor';

const CODE_LENGTH = 6;
const CONFIRM_ERROR_BAG = 'confirmTwoFactorAuthentication';

type SetupData = { qrCodeSvg: string; secretKey: string };

function RecoveryCodes() {
    const http = useHttp();
    const regenerateForm = useForm({});
    const [codes, setCodes] = useState<string[] | null>(null);
    const isBusy = http.processing || regenerateForm.processing;

    const load = async () => setCodes((await http.get(recoveryCodes().url)) as string[]);

    const regenerate = () =>
        regenerateForm.submit(regenerateRecoveryCodes(), { preserveScroll: true, onSuccess: () => void load() });

    return (
        <div className="flex flex-col gap-3 rounded-lg border p-4">
            <p className="text-sm text-muted-foreground">
                Recovery codes let you in if you lose your device. Keep them in a password manager.
            </p>
            <div className="flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => (codes ? setCodes(null) : void load())}
                    disabled={isBusy}
                >
                    {codes ? <EyeOff /> : <Eye />}
                    {codes ? 'Hide recovery codes' : 'View recovery codes'}
                </Button>
                {codes && (
                    <Button variant="outline" size="sm" onClick={regenerate} disabled={isBusy}>
                        <RefreshCw /> Regenerate codes
                    </Button>
                )}
            </div>
            {codes && (
                <ul aria-label="Recovery codes" className="grid gap-1 rounded-md bg-muted p-4 font-mono text-sm">
                    {codes.map((code) => (
                        <li key={code}>{code}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export function SetupDialog({ setup, onClose }: { setup: SetupData; onClose: () => void }) {
    const form = useForm({ code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit(confirm(), {
            preserveScroll: true,
            errorBag: CONFIRM_ERROR_BAG,
            onSuccess: onClose,
            onError: () => form.reset(),
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} noValidate className="flex flex-col gap-6">
                    <DialogHeader>
                        <DialogTitle>Turn on two factor authentication</DialogTitle>
                        <DialogDescription>
                            Scan the QR code with your authenticator app, or enter the setup key. Then type the code the
                            app shows.
                        </DialogDescription>
                    </DialogHeader>
                    <div
                        className="mx-auto size-48 rounded-lg bg-white p-2 [&_svg]:size-full"
                        dangerouslySetInnerHTML={{ __html: setup.qrCodeSvg }}
                    />
                    <p className="text-center font-mono text-sm break-all text-muted-foreground">{setup.secretKey}</p>
                    <Field data-invalid={!!form.errors.code || undefined} className="items-center">
                        <InputOTP
                            name="code"
                            maxLength={CODE_LENGTH}
                            pattern={REGEXP_ONLY_DIGITS}
                            autoFocus
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
                    <DialogFooter>
                        <Button type="submit" disabled={form.processing || form.data.code.length < CODE_LENGTH}>
                            {form.processing && <Spinner />}
                            Confirm
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Turning two factor on is two steps: enable creates the secret, confirm proves the app has it.
 */
export function useTwoFactorSetup() {
    const http = useHttp();
    const [setup, setSetup] = useState<SetupData | null>(null);
    const [processing, setProcessing] = useState(false);

    const loadSetup = async () => {
        const [qr, key] = await Promise.all([http.get(qrCode().url), http.get(secretKey().url)]);

        setSetup({ qrCodeSvg: (qr as { svg: string }).svg, secretKey: (key as { secretKey: string }).secretKey });
    };

    const start = () =>
        router.post(
            enable().url,
            {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => void loadSetup(),
            },
        );

    return { setup, start, processing, setProcessing, close: () => setSetup(null) };
}

export function TwoFactorPanel({ enabled }: { enabled: boolean }) {
    const { setup, start: turnOn, processing, setProcessing, close } = useTwoFactorSetup();

    const turnOff = () =>
        router.delete(disable().url, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });

    return (
        <div className="flex flex-col gap-4">
            <div>
                {enabled ? (
                    <Badge className="bg-success/15 text-success">
                        <ShieldCheck /> Enabled
                    </Badge>
                ) : (
                    <Badge variant="outline">Disabled</Badge>
                )}
            </div>
            <p className="text-sm text-muted-foreground">
                {enabled
                    ? 'Each log in asks for a code from your authenticator app.'
                    : 'Ask for a code from an authenticator app on each log in, on top of your password.'}
            </p>
            {enabled && <RecoveryCodes />}
            <div>
                {enabled ? (
                    <Button variant="destructive" onClick={turnOff} disabled={processing}>
                        {processing ? <Spinner /> : <ShieldBan />}
                        Turn off two factor
                    </Button>
                ) : (
                    <Button onClick={turnOn} disabled={processing}>
                        {processing ? <Spinner /> : <ShieldCheck />}
                        Turn on two factor
                    </Button>
                )}
            </div>
            {setup && <SetupDialog setup={setup} onClose={close} />}
        </div>
    );
}
