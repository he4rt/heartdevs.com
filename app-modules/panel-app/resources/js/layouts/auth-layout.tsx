import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashToaster } from '@/components/flash-toaster';
import { welcome } from '@/wayfinder/routes/namespaced/panel-app';

export default function AuthLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-svh items-center justify-center bg-muted/40 p-4">
            <div className="flex w-full max-w-sm flex-col gap-6">
                <Link
                    href={welcome().url}
                    prefetch
                    className="flex items-center justify-center gap-2 text-lg font-semibold"
                >
                    <span className="size-3 rotate-45 rounded-sm bg-primary" />
                    He4rt
                </Link>
                {children}
            </div>
            <FlashToaster />
        </div>
    );
}
