import '../../../../resources/css/app.css';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@/components/theme-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import { flushPrefetchAfterWrites } from '@/lib/prefetch';
import AppLayout from '@app/layouts/app-layout';
import AuthLayout from '@app/layouts/auth-layout';
import { ImpersonationExit } from '@app/components/impersonation-exit';

const pages = import.meta.glob<{ default: ResolvedComponent }>('./features/**/pages/*.tsx');

/**
 * A page picks its layout by name: auth/* in a card, home/welcome alone, the rest in the sidebar.
 */
function layoutFor(name: string): (children: ReactNode) => ReactNode {
    if (name.startsWith('auth/')) {
        return (children) => <AuthLayout>{children}</AuthLayout>;
    }

    if (name === 'home/welcome') {
        return (children) => children;
    }

    return (children) => <AppLayout>{children}</AppLayout>;
}

createInertiaApp({
    title: (title) => (title ? `${title} · He4rt` : 'He4rt'),
    resolve: async (name) => {
        const suffix = `/${name.replace(/\/([^/]+)$/, '/pages/$1')}.tsx`;
        const match = Object.keys(pages).find((path) => path.endsWith(suffix));

        if (!match) {
            throw new Error(`Inertia page "${name}" not found (expected features/**${suffix}).`);
        }

        const page = await pages[match]!();

        const layout = layoutFor(name);

        // Every page starts with the impersonation exit, so an admin can always leave.
        page.default.layout ??= (children: ReactNode) => (
            <>
                <ImpersonationExit />
                {layout(children)}
            </>
        );

        return page.default;
    },
    setup({ el, App, props }) {
        createRoot(el!).render(
            <ThemeProvider>
                <TooltipProvider>
                    <App {...props} />
                </TooltipProvider>
            </ThemeProvider>,
        );
    },
    progress: { color: 'oklch(0.627 0.265 303.9)' },
});

flushPrefetchAfterWrites();
