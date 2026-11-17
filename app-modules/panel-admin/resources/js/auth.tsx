import '../../../../resources/css/app.css';
import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@/components/theme-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import AuthLayout from '@admin/layouts/auth-layout';

const pages = import.meta.glob<{ default: ResolvedComponent }>('./features/auth/pages/*.tsx');

createInertiaApp({
    title: (title) => (title ? `${title} · He4rt Admin` : 'He4rt Admin'),
    resolve: async (name) => {
        const page = await pages[`./features/${name.replace(/\/([^/]+)$/, '/pages/$1')}.tsx`]!();

        page.default.layout ??= (children: ReactNode) => <AuthLayout>{children}</AuthLayout>;

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
