import { Link } from '@inertiajs/react';
import { cn } from 'cn';

type Tab = He4rt.PanelAdmin.Features.System.Settings.Data.SettingsTabData;

export function SettingsTabs({ tabs }: { tabs: Tab[] }) {
    return (
        <nav aria-label="Settings" className="flex gap-1 border-b">
            {tabs.map((tab) => (
                <Link
                    key={tab.key}
                    href={tab.href}
                    prefetch
                    aria-current={tab.current ? 'page' : undefined}
                    className={cn(
                        '-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-muted-foreground hover:text-foreground',
                        tab.current && 'border-primary font-medium text-foreground',
                    )}
                >
                    {tab.label}
                </Link>
            ))}
        </nav>
    );
}
