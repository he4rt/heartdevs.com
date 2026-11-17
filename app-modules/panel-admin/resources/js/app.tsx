import '../../../../resources/css/app.css';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@/components/theme-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import { flushPrefetchAfterWrites } from '@/lib/prefetch';
import AdminLayout from '@admin/layouts/admin-layout';

const pages = import.meta.glob<{ default: ResolvedComponent }>(['./features/**/pages/*.tsx', '!./features/auth/**']);

createInertiaApp({
    title: (title) => (title ? `${title} · He4rt Admin` : 'He4rt Admin'),
    resolve: async (name) => {
        const suffix = `/${name.replace(/\/([^/]+)$/, '/pages/$1')}.tsx`;
        const match = Object.keys(pages).find((path) => path.endsWith(suffix));

        if (!match) {
            throw new Error(`Inertia page "${name}" not found (expected features/**${suffix}).`);
        }

        const page = await pages[match]!();

        page.default.layout ??= (children: ReactNode) => <AdminLayout>{children}</AdminLayout>;

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
