import { router } from '@inertiajs/react';
import { usePasskeyRegister } from '@laravel/passkeys/react';
import { KeyRound, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { FieldError, FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { destroy } from '@/wayfinder/routes/passkey';
import { TextField } from '@/components/text-field';

/**
 * The PasskeyData of each panel has this shape.
 */
type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    createdAt: string;
    lastUsedAt: string | null;
};

const BROWSERS = [
    [/Edg/, 'Edge'],
    [/OPR|Opera/, 'Opera'],
    [/Firefox|FxiOS/, 'Firefox'],
    [/Chrome|CriOS/, 'Chrome'],
    [/Safari/, 'Safari'],
] as const;

const SYSTEMS = [
    [/iPhone/, 'iPhone'],
    [/iPad/, 'iPad'],
    [/Android/, 'Android'],
    [/Mac/, 'Mac'],
    [/Windows/, 'Windows'],
    [/Linux/, 'Linux'],
] as const;

function suggestedName(): string {
    const agent = navigator.userAgent;
    const browser = BROWSERS.find(([pattern]) => pattern.test(agent))?.[1];
    const system = SYSTEMS.find(([pattern]) => pattern.test(agent))?.[1];

    return [browser, system].filter(Boolean).join(' on ');
}

/**
 * The passkey library saves the key with its own fetch, so the page asks for the new list. A visit, not
 * router.reload(): a reload is async, and Inertia ignores an async response when the asset version changed
 * (after a deploy), so the list would stay old. A visit reloads the whole page in that case.
 */
function refreshPasskeys(pageUrl: string): void {
    router.visit(pageUrl, { only: ['passkeys'], preserveScroll: true, preserveState: true });
}

function AddPasskey({ pageUrl }: { pageUrl: string }) {
    const [name, setName] = useState<string | null>(null);
    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            setName(null);
            refreshPasskeys(pageUrl);
        },
    });

    if (!isSupported) {
        return <p className="text-sm text-muted-foreground">This browser does not support passkeys.</p>;
    }

    if (name === null) {
        return (
            <div>
                <Button variant="outline" onClick={() => setName(suggestedName())}>
                    <Plus /> Add a passkey
                </Button>
            </div>
        );
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        void register(name);
    };

    return (
        <form onSubmit={submit} noValidate className="rounded-lg border p-4">
            <FieldGroup>
                <TextField
                    id="passkey_name"
                    label="Passkey name"
                    autoFocus
                    required
                    value={name}
                    onValueChange={setName}
                />
                {error && <FieldError>{error}</FieldError>}
                <div className="flex gap-2">
                    <Button type="submit" disabled={isLoading || name.trim() === ''}>
                        {isLoading ? <Spinner /> : <KeyRound />}
                        Create passkey
                    </Button>
                    <Button type="button" variant="ghost" onClick={() => setName(null)}>
                        Cancel
                    </Button>
                </div>
            </FieldGroup>
        </form>
    );
}

/**
 * pageUrl is the page that lists the passkeys, so the list reloads after a new key.
 */
export function PasskeysPanel({ passkeys, pageUrl }: { passkeys: Passkey[]; pageUrl: string }) {
    const [removing, setRemoving] = useState<Passkey | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <div className="flex flex-col gap-4">
            {passkeys.length === 0 ? (
                <Empty className="rounded-lg border py-8">
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <KeyRound />
                        </EmptyMedia>
                        <EmptyTitle>No passkeys yet</EmptyTitle>
                        <EmptyDescription>Add a passkey to log in without a password.</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {passkeys.map((passkey) => (
                        <li key={passkey.id} className="flex items-center gap-3 p-4">
                            <KeyRound className="size-5 text-muted-foreground" />
                            <div className="flex min-w-0 flex-1 flex-col">
                                <span className="truncate font-medium">{passkey.name}</span>
                                <span className="text-xs text-muted-foreground">
                                    {passkey.authenticator ? `${passkey.authenticator} · ` : ''}
                                    Added {formatDate(passkey.createdAt)}
                                    {passkey.lastUsedAt && ` · Last used ${formatDate(passkey.lastUsedAt)}`}
                                </span>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={`Delete ${passkey.name}`}
                                onClick={() => setRemoving(passkey)}
                            >
                                <Trash2 />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
            <AddPasskey pageUrl={pageUrl} />
            {removing && (
                <ConfirmDialog
                    heading={`Delete ${removing.name}?`}
                    text="You can no longer log in with this passkey."
                    confirmLabel="Yes, delete"
                    destructive
                    processing={processing}
                    onClose={() => setRemoving(null)}
                    onConfirm={() =>
                        router.delete(destroy(removing.id).url, {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                            onSuccess: () => setRemoving(null),
                        })
                    }
                />
            )}
        </div>
    );
}
