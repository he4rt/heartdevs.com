import {
    BookOpen,
    Circle,
    Flag,
    History,
    LayoutDashboard,
    Mail,
    ScrollText,
    Settings,
    ShieldCheck,
    Users,
    UsersRound,
    type LucideIcon,
} from 'lucide-react';

const ICONS: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    users: Users,
    'shield-check': ShieldCheck,
    'users-round': UsersRound,
    history: History,
    'scroll-text': ScrollText,
    mail: Mail,
    'book-open': BookOpen,
    settings: Settings,
    flag: Flag,
};

export function navigationIcon(name: string): LucideIcon {
    return ICONS[name] ?? Circle;
}
