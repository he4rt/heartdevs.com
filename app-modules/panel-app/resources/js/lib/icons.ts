import { Circle, LayoutDashboard, type LucideIcon } from 'lucide-react';

const ICONS: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
};

export function navigationIcon(name: string): LucideIcon {
    return ICONS[name] ?? Circle;
}
