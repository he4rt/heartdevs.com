import { useForm } from '@inertiajs/react';
import { Undo2, VenetianMask } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { leave } from '@/wayfinder/routes/identity/impersonation';
import { useAccount } from '@app/hooks/use-account';

/**
 * While a super admin is signed in as this user, every page shows this pill at the top, whatever its layout.
 * The spacer above the page keeps the pill off the page content.
 */
export function ImpersonationExit() {
    const account = useAccount();
    const form = useForm({});

    if (!account?.impersonatorName) {
        return null;
    }

    return (
        <>
            <div aria-hidden className="h-16" />
            <aside
                aria-label="Impersonation"
                className="fixed inset-x-4 top-3 z-50 mx-auto flex w-fit max-w-[calc(100%-2rem)] items-center gap-3 rounded-full border border-warning/40 bg-popover/95 py-1.5 pr-1.5 pl-4 text-sm shadow-lg backdrop-blur"
            >
                <VenetianMask className="size-4 shrink-0 text-warning" />
                <span
                    className="min-w-0 truncate"
                    title={`${account.impersonatorName} is signed in as ${account.user.name}`}
                >
                    Signed in as <span className="font-medium">{account.user.name}</span>
                    <span className="hidden text-muted-foreground sm:inline"> by {account.impersonatorName}</span>
                </span>
                <Button
                    size="sm"
                    variant="outline"
                    className="shrink-0 rounded-full"
                    onClick={() => form.submit(leave())}
                    disabled={form.processing}
                >
                    {form.processing ? <Spinner /> : <Undo2 />} Back to admin
                </Button>
            </aside>
        </>
    );
}
