import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight, Flag, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { destroy, store } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags/gate';
import { index as teams } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags/teams';
import { index as users } from '@/wayfinder/routes/namespaced/panel-admin/feature-flags/users';
import { HelpLink } from '@admin/components/help-link';

type Flag = He4rt.PanelAdmin.Features.System.FeatureFlags.Data.FeatureFlagData;

type Props = {
    flags: Flag[];
    can: { update: boolean };
};

const SCOPE_NOUN: Record<string, [string, string]> = {
    team: ['team', 'teams'],
    user: ['user', 'users'],
};

function enabledLabel(flag: Flag): string {
    const [one, many] = SCOPE_NOUN[flag.scope.value] ?? ['', ''];

    return `${flag.enabledCount} ${flag.enabledCount === 1 ? one : many}`;
}

function scopeRoute(flag: Flag): string | null {
    if (flag.scope.value === 'team') {
        return teams(flag.key).url;
    }

    return flag.scope.value === 'user' ? users(flag.key).url : null;
}

export default function FeatureFlagsIndex({ flags, can }: Props) {
    const [pendingKeys, setPendingKeys] = useState<string[]>([]);

    const toggleGate = (flag: Flag) =>
        router
            .optimistic<Props>((props) => ({
                flags: props.flags.map((item) =>
                    item.key === flag.key ? { ...item, gateOpen: !flag.gateOpen } : item,
                ),
            }))
            .visit(flag.gateOpen ? destroy(flag.key) : store(flag.key), {
                preserveScroll: true,
                only: ['flags'],
                onStart: () => setPendingKeys((keys) => [...keys, flag.key]),
                onFinish: () => setPendingKeys((keys) => keys.filter((key) => key !== flag.key)),
            });

    return (
        <>
            <Head title="Feature flags" />
            <PageHeader
                heading="Feature flags"
                description="Turn features on for everyone, or team by team and user by user."
                actions={<HelpLink path="system/feature-flags" />}
            />
            <div className="overflow-hidden rounded-xl border bg-card">
                <Table>
                    <TableHeader>
                        <TableRow className="hover:bg-transparent">
                            <TableHead>Flag</TableHead>
                            <TableHead>Scope</TableHead>
                            <TableHead>Gate</TableHead>
                            <TableHead>Enabled for</TableHead>
                            <TableHead className="w-10" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {flags.length === 0 && (
                            <TableRow className="hover:bg-transparent">
                                <TableCell colSpan={5} className="p-0">
                                    <Empty className="py-10">
                                        <EmptyHeader>
                                            <EmptyMedia variant="icon">
                                                <Flag />
                                            </EmptyMedia>
                                            <EmptyTitle>No feature flags yet</EmptyTitle>
                                            <EmptyDescription>
                                                A module declares its flags in src/Features and registers them in its
                                                service provider.
                                            </EmptyDescription>
                                        </EmptyHeader>
                                    </Empty>
                                </TableCell>
                            </TableRow>
                        )}
                        {flags.map((flag) => {
                            const route = scopeRoute(flag);
                            const isWaitingForGate = !flag.gateOpen && flag.enabledCount > 0;

                            return (
                                <TableRow key={flag.key}>
                                    <TableCell className="max-w-md min-w-0 whitespace-normal">
                                        <div className="flex flex-col gap-0.5">
                                            <span className="font-medium">{flag.label}</span>
                                            <span className="text-xs text-muted-foreground">{flag.description}</span>
                                            <span className="data-id text-xs text-muted-foreground">{flag.key}</span>
                                            {isWaitingForGate && (
                                                <span className="flex items-center gap-1 text-xs text-warning">
                                                    <TriangleAlert className="size-3.5" />
                                                    The gate is closed, so the {enabledLabel(flag)} do not see it.
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <ToneBadge tone={flag.scope.tone}>{flag.scope.label}</ToneBadge>
                                    </TableCell>
                                    <TableCell>
                                        <label className="flex items-center gap-2 text-sm">
                                            <Switch
                                                checked={flag.gateOpen}
                                                disabled={!can.update || pendingKeys.includes(flag.key)}
                                                onCheckedChange={() => toggleGate(flag)}
                                                aria-label={`${flag.label} gate`}
                                            />
                                            <span className="w-12">{flag.gateOpen ? 'Open' : 'Closed'}</span>
                                        </label>
                                    </TableCell>
                                    <TableCell>
                                        <span className="tabular-nums">{route ? enabledLabel(flag) : 'Everyone'}</span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {route && (
                                            <Link
                                                href={route}
                                                prefetch
                                                aria-label={`Manage ${flag.label}`}
                                                className="inline-flex text-muted-foreground hover:text-foreground"
                                            >
                                                <ChevronRight className="size-4" />
                                            </Link>
                                        )}
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}
