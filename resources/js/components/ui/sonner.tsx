'use client';

import { useTheme } from 'next-themes';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import { CircleCheckIcon, InfoIcon, TriangleAlertIcon, OctagonXIcon, Loader2Icon } from 'lucide-react';

const Toaster = ({ ...props }: ToasterProps) => {
    const { theme = 'system' } = useTheme();

    return (
        <Sonner
            theme={theme as ToasterProps['theme']}
            className="toaster group"
            icons={{
                success: <CircleCheckIcon className="size-4" />,
                info: <InfoIcon className="size-4" />,
                warning: <TriangleAlertIcon className="size-4" />,
                error: <OctagonXIcon className="size-4" />,
                loading: <Loader2Icon className="size-4 animate-spin" />,
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'var(--radius)',
                    '--success-bg': 'var(--popover)',
                    '--success-text': 'var(--success)',
                    '--success-border': 'color-mix(in oklch, var(--success) 35%, var(--popover))',
                    '--error-bg': 'var(--popover)',
                    '--error-text': 'var(--destructive)',
                    '--error-border': 'color-mix(in oklch, var(--destructive) 35%, var(--popover))',
                    '--warning-bg': 'var(--popover)',
                    '--warning-text': 'var(--warning)',
                    '--warning-border': 'color-mix(in oklch, var(--warning) 35%, var(--popover))',
                    '--info-bg': 'var(--popover)',
                    '--info-text': 'var(--popover-foreground)',
                    '--info-border': 'var(--border)',
                } as React.CSSProperties
            }
            toastOptions={{
                classNames: {
                    toast: 'cn-toast',
                },
            }}
            {...props}
        />
    );
};

export { Toaster };
