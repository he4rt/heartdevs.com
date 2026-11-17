import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { PageHeader } from '@/components/page-header';
import { cn } from 'cn';
import { edit as appearance } from '@/wayfinder/routes/namespaced/panel-app/settings/appearance';
import { edit as preferences } from '@/wayfinder/routes/namespaced/panel-app/settings/preferences';
import { edit as profile } from '@/wayfinder/routes/namespaced/panel-app/settings/profile';
import { edit as security } from '@/wayfinder/routes/namespaced/panel-app/settings/security';

/**
 * The security page turns off an unconfirmed two factor setup on GET and asks for the password, so it is never prefetched.
 */
const SECTIONS = [
    { label: 'Profile', route: profile, prefetch: true },
    { label: 'Security', route: security, prefetch: false },
    { label: 'Preferences', route: preferences, prefetch: true },
    { label: 'Appearance', route: appearance, prefetch: true },
];

export function SettingsLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (
        <>
            <PageHeader heading="Settings" description="Manage your profile and account settings." />
            <div className="flex flex-col gap-8 lg:flex-row">
                <nav aria-label="Settings" className="flex gap-1 lg:w-48 lg:flex-col">
                    {SECTIONS.map(({ label, route, prefetch }) => {
                        const href = route().url;
                        const isCurrent = currentPath === href;

                        return (
                            <Link
                                key={label}
                                href={href}
                                prefetch={prefetch}
                                aria-current={isCurrent ? 'page' : undefined}
                                className={cn(
                                    'rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground',
                                    isCurrent && 'bg-muted font-medium text-foreground',
                                )}
                            >
                                {label}
                            </Link>
                        );
                    })}
                </nav>
                <div className="flex max-w-2xl flex-1 flex-col gap-10">{children}</div>
            </div>
        </>
    );
}

export function SettingsSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="flex flex-col gap-6">
            <header className="flex flex-col gap-1">
                <h2 className="text-base font-semibold">{title}</h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </header>
            {children}
        </section>
    );
}
