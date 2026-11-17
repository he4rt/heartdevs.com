import { Link, router } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, Monitor, Moon, Settings, ShieldCheck, Sun } from 'lucide-react';
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
import { logout } from '@/wayfinder/routes';
import { home as admin } from '@/wayfinder/routes/namespaced/panel-admin';
import { dashboard } from '@/wayfinder/routes/namespaced/panel-app';
import { edit as settings } from '@/wayfinder/routes/namespaced/panel-app/settings/profile';
import { useAccount } from '@app/hooks/use-account';
import { navigationIcon } from '@app/lib/icons';

type Account = He4rt.PanelApp.Shared.Data.AccountData;

const THEMES = [
    { value: 'system', label: 'System', Icon: Monitor },
    { value: 'light', label: 'Light', Icon: Sun },
    { value: 'dark', label: 'Dark', Icon: Moon },
] as const;

function UserMenu({ account }: { account: Account }) {
    const { theme, setTheme } = useTheme();
    const { isMobile } = useSidebar();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger
                        render={<SidebarMenuButton size="lg" className="data-popup-open:bg-sidebar-accent" />}
                    >
                        <Avatar className="size-8 rounded-lg">
                            <AvatarFallback className="rounded-lg bg-primary/15 text-primary">
                                {account.user.initials}
                            </AvatarFallback>
                        </Avatar>
                        <div className="grid flex-1 text-left text-sm leading-tight">
                            <span className="truncate font-medium">{account.user.name}</span>
                            <span className="truncate text-xs text-muted-foreground">{account.user.email}</span>
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
                        <DropdownMenuItem render={<Link href={settings().url} prefetch />}>
                            <Settings /> Settings
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

function AppSidebar({ account }: { account: Account }) {
    return (
        <Sidebar collapsible="icon">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" render={<Link href={dashboard().url} prefetch />}>
                            <span className="flex size-8 items-center justify-center rounded-lg bg-primary">
                                <span className="size-2.5 rotate-45 rounded-sm bg-primary-foreground" />
                            </span>
                            <span className="font-semibold">He4rt</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>
            <SidebarContent>
                {account.navigation.map((group) => (
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
                {account.canAccessAdmin && (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            {/* A plain link: the admin is another bundle, so the visit reloads the page. */}
                            <SidebarMenuButton tooltip="Admin" render={<a href={admin().url} />}>
                                <ShieldCheck />
                                <span>Admin</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
                <UserMenu account={account} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const account = useAccount();

    useAccentColor(account?.user.themeColor ?? null);

    if (!account) {
        return children;
    }

    return (
        <SidebarProvider>
            <AppSidebar account={account} />
            <SidebarInset>
                <header className="flex h-14 shrink-0 items-center gap-2 border-b px-4">
                    <SidebarTrigger className="-ml-1" />
                    <Separator orientation="vertical" className="mr-2 data-[orientation=vertical]:h-4" />
                    <span className="text-sm font-medium">He4rt</span>
                </header>
                <main className="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-10">{children}</main>
            </SidebarInset>
            <FlashToaster />
        </SidebarProvider>
    );
}
