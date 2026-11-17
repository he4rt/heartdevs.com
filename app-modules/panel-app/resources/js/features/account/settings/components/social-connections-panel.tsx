import { router } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { redirect as connect } from '@/wayfinder/routes/namespaced/panel-app/social-accounts';
import { destroy } from '@/wayfinder/routes/namespaced/panel-app/settings/social-accounts';

type Connection = He4rt.PanelApp.Features.Account.Settings.Data.SocialConnectionData;

export function SocialConnectionsPanel({ connections }: { connections: Connection[] }) {
    const [removing, setRemoving] = useState<Connection | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <ul className="divide-y rounded-lg border">
                {connections.map((connection) => (
                    <li key={connection.provider.value} className="flex items-center gap-3 p-4">
                        <div className="flex min-w-0 flex-1 flex-col">
                            <span className="font-medium">{connection.provider.label}</span>
                            <span className="text-xs text-muted-foreground">
                                {connection.connectedAt
                                    ? `Connected ${formatDate(connection.connectedAt)}`
                                    : 'Not connected'}
                                {connection.accountId && !connection.canDisconnect && ' · Set a password first'}
                            </span>
                        </div>
                        {connection.accountId ? (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!connection.canDisconnect}
                                onClick={() => setRemoving(connection)}
                            >
                                Disconnect
                            </Button>
                        ) : (
                            /* A plain link: the provider's page is outside the app. */
                            <Button
                                variant="outline"
                                size="sm"
                                nativeButton={false}
                                render={<a href={connect(connection.provider.value).url} />}
                            >
                                Connect
                            </Button>
                        )}
                    </li>
                ))}
            </ul>
            {removing?.accountId && (
                <ConfirmDialog
                    heading={`Disconnect ${removing.provider.label}?`}
                    text={`You can no longer log in with this ${removing.provider.label} account.`}
                    confirmLabel="Yes, disconnect"
                    destructive
                    processing={processing}
                    onClose={() => setRemoving(null)}
                    onConfirm={() =>
                        router.delete(destroy(removing.accountId ?? '').url, {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                            onSuccess: () => setRemoving(null),
                        })
                    }
                />
            )}
        </>
    );
}
