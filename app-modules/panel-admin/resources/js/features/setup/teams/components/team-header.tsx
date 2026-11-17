import { Link, router } from '@inertiajs/react';
import { ArchiveRestore, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatDate } from '@/lib/format';
import { destroy, forceDelete, restore, show } from '@/wayfinder/routes/namespaced/panel-admin/teams';
import { index as members } from '@/wayfinder/routes/namespaced/panel-admin/teams/members';

type Team = He4rt.PanelAdmin.Features.Setup.Teams.Data.TeamDetailData;

export type TeamAbilities = { delete: boolean; restore: boolean; forceDelete: boolean };

type TeamHeaderProps = {
    team: Team;
    tab: 'details' | 'members';
    can: TeamAbilities;
};

type Confirm = 'delete' | 'force-delete' | null;

export function TeamHeader({ team, tab, can }: TeamHeaderProps) {
    const [confirm, setConfirm] = useState<Confirm>(null);
    const [processing, setProcessing] = useState(false);
    const visitOptions = {
        preserveScroll: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
        onSuccess: () => setConfirm(null),
    };

    return (
        <>
            <PageHeader
                heading={
                    <span className="flex flex-wrap items-center gap-2">
                        {team.name}
                        <ToneBadge tone={team.status.tone}>{team.status.label}</ToneBadge>
                    </span>
                }
                description={team.slug}
                actions={
                    <>
                        {can.restore && (
                            <Button
                                variant="outline"
                                disabled={processing}
                                onClick={() => router.visit(restore(team.id), visitOptions)}
                            >
                                <ArchiveRestore /> Restore
                            </Button>
                        )}
                        {can.forceDelete && (
                            <Button variant="destructive" onClick={() => setConfirm('force-delete')}>
                                <Trash2 /> Delete permanently
                            </Button>
                        )}
                        {can.delete && (
                            <Button variant="outline" onClick={() => setConfirm('delete')}>
                                <Trash2 /> Delete
                            </Button>
                        )}
                    </>
                }
            />
            {team.deletedAt && (
                <Alert variant="destructive" className="mb-6">
                    <Trash2 />
                    <AlertTitle>This team is in the trash</AlertTitle>
                    <AlertDescription>
                        Deleted on {formatDate(team.deletedAt)}. Restore it to make changes.
                    </AlertDescription>
                </Alert>
            )}
            <Tabs value={tab} className="mb-6">
                <TabsList>
                    <TabsTrigger
                        value="details"
                        nativeButton={false}
                        render={<Link href={show(team.id).url} prefetch preserveScroll />}
                    >
                        Details
                    </TabsTrigger>
                    <TabsTrigger
                        value="members"
                        nativeButton={false}
                        render={<Link href={members(team.id).url} prefetch preserveScroll />}
                    >
                        Members <span className="text-muted-foreground tabular-nums">{team.membersCount}</span>
                    </TabsTrigger>
                </TabsList>
            </Tabs>
            {confirm === 'delete' && (
                <ConfirmDialog
                    heading={`Delete ${team.name}?`}
                    text="The team moves to the trash. Its members lose access until you restore it."
                    confirmLabel="Yes, delete"
                    destructive
                    processing={processing}
                    onClose={() => setConfirm(null)}
                    onConfirm={() => router.visit(destroy(team.id), visitOptions)}
                />
            )}
            {confirm === 'force-delete' && (
                <ConfirmDialog
                    heading={`Delete ${team.name} permanently?`}
                    text="The team and its memberships are removed. This cannot be undone."
                    confirmLabel="Yes, delete permanently"
                    destructive
                    processing={processing}
                    onClose={() => setConfirm(null)}
                    onConfirm={() => router.visit(forceDelete(team.id), visitOptions)}
                />
            )}
        </>
    );
}
