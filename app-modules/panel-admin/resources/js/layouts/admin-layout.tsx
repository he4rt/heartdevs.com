import { Link, router, usePage } from '@inertiajs/react';
import { AppWindow, ChevronsUpDown, LogOut, Monitor, Moon, Sun, UserPen } from 'lucide-react';
import { useTheme } from 'next-themes';
import type { ReactNode } from 'react';
import { FlashToaster } from '@/components/flash-toaster';
import { useAccentColor } from '@/hooks/use-accent-color';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarRail,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import { home, logout } from '@/wayfinder/routes/namespaced/panel-admin';
import { edit as profile } from '@/wayfinder/routes/namespaced/panel-admin/profile';
import { dashboard as appDashboard } from '@/wayfinder/routes/namespaced/panel-app';
import { navigationIcon } from '@admin/lib/icons';

const THEMES = [
    { value: 'system', label: 'System', Icon: Monitor },
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
] as const;

function UserMenu() {
    const { app } = usePage().props;
    const { theme, setTheme } = useTheme();
    const { isMobile } = useSidebar();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger
                        render={
                            <SidebarMenuButton size="lg" className="data-popup-open:bg-sidebar-accent" data-user-menu />
                        }
                    >
                        <Avatar className="size-8 rounded-lg">
                            <AvatarFallback className="rounded-lg bg-primary/15 text-primary">
                                {app.user.initials}
                            </AvatarFallback>
                        </Avatar>
                        <div className="grid flex-1 text-left text-sm leading-tight">
                            <span className="truncate font-medium">{app.user.name}</span>
                            <span className="truncate text-xs text-muted-foreground">{app.user.email}</span>
                        </div>
                        <ChevronsUpDown className="ml-auto size-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="w-56" side={isMobile ? 'bottom' : 'right'} align="end">
                        <DropdownMenuRadioGroup value={theme} onValueChange={setTheme}>
                            <DropdownMenuLabel>Theme</DropdownMenuLabel>
                            {THEMES.map(({ value, label, Icon }) => (
                                <DropdownMenuRadioItem key={value} value={value}>
                                    <Icon /> {label}
                                </DropdownMenuRadioItem>
                            ))}
                        </DropdownMenuRadioGroup>
                        <DropdownMenuSeparator />
                        {/* Not prefetched: the page can ask for the password. */}
                        <DropdownMenuItem render={<Link href={profile().url} />}>
                            <UserPen /> Profile
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => router.post(logout().url)}>
                            <LogOut /> Log out
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

function AppSidebar() {
    const { app } = usePage().props;

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" render={<Link href={home().url} prefetch />}>
                            <span className="flex size-8 items-center justify-center rounded-lg bg-primary">
                                <span className="size-2.5 rotate-45 rounded-sm bg-primary-foreground" />
                            </span>
                            <span className="font-semibold">He4rt</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                {app.navigation.map((group) => (
                    <SidebarGroup key={group.key}>
                        {group.heading && <SidebarGroupLabel>{group.heading}</SidebarGroupLabel>}
                        <SidebarMenu>
                            {group.items.map((item) => {
                                const Icon = navigationIcon(item.icon);

                                return (
                                    <SidebarMenuItem key={item.key}>
                                        <SidebarMenuButton
                                            isActive={item.current}
                                            tooltip={item.label}
                                            render={
                                                <Link
                                                    href={item.href}
                                                    prefetch
                                                    aria-current={item.current ? 'page' : undefined}
                                                />
                                            }
                                        >
                                            <Icon />
                                            <span>{item.label}</span>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>
            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {/* A plain link: the app is another bundle, so the visit reloads the page. */}
                        <SidebarMenuButton tooltip="App" render={<a href={appDashboard().url} />}>
                            <AppWindow />
                            <span>App</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <UserMenu />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}

export default function AdminLayout({ children }: { children: ReactNode }) {
    const { app } = usePage().props;

    useAccentColor(app?.user.themeColor ?? null);

    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                    <SidebarTrigger className="-ml-1" />
                    <Separator orientation="vertical" className="mr-2 data-[orientation=vertical]:h-4" />
                    <span className="text-sm font-medium">He4rt Admin</span>
                </header>
                <main className="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-10">{children}</main>
            </SidebarInset>
            <FlashToaster />
        </SidebarProvider>
    );
}
