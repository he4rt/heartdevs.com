import { Head, router } from '@inertiajs/react';
import { Plus, UserMinus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { createDataTableColumns, DataTable } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useTableQuery } from '@/hooks/use-table-query';
import { formatDate } from '@/lib/format';
import type { Page } from '@/lib/page';
import { destroy, store } from '@/wayfinder/routes/namespaced/panel-admin/teams/members';
import { UserPicker } from '@admin/components/user-picker';
import { TeamHeader } from '../components/team-header';

type Team = He4rt.PanelAdmin.Features.Setup.Teams.Data.TeamDetailData;
type Member = He4rt.PanelAdmin.Features.Setup.Teams.Members.Data.TeamMemberData;

type Props = {
    team: Team;
    members: Page<Member>;
    query: Record<string, unknown>;
    perPageOptions: number[];
    can: { update: boolean };
};

const ONLY = ['members', 'team'];
const DEFAULT_PER_PAGE = 25;
const NO_LIFECYCLE_ACTIONS = { delete: false, restore: false, forceDelete: false };

const column = createDataTableColumns<Member>();

export default function TeamMembers({ team, members, query, perPageOptions, can }: Props) {
    const table = useTableQuery(query, DEFAULT_PER_PAGE, ONLY);
    const [removing, setRemoving] = useState<Member | null>(null);
    const [processing, setProcessing] = useState(false);
    const visitOptions = {
        preserveScroll: true,
        only: ONLY,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
    };

    const columns = column.columns([
        column.accessor('name', {
            header: 'Name',
            enableSorting: true,
            cell: ({ row: { original: member } }) => (
                <span className="flex items-center gap-2 font-medium">
                    {member.name}
                    {member.isOwner && <Badge>Owner</Badge>}
                </span>
            ),
        }),
        column.accessor('email', {
            header: 'Email',
            enableSorting: true,
            cell: ({ getValue }) => <span className="data-id">{getValue()}</span>,
        }),
        column.accessor('joinedAt', {
            id: 'joined_at',
            header: 'Joined',
            enableSorting: true,
            cell: ({ getValue }) => {
                const joinedAt = getValue();

                return <span className="data-id">{joinedAt ? formatDate(joinedAt) : '—'}</span>;
            },
        }),
        column.display({
            id: 'actions',
            meta: { className: 'text-right' },
            cell: ({ row: { original: member } }) =>
                can.update &&
                (member.isOwner ? (
                    <Tooltip>
                        <TooltipTrigger render={<span tabIndex={0} />}>
                            <Button variant="ghost" size="sm" disabled>
                                <UserMinus /> Remove
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Transfer the ownership first</TooltipContent>
                    </Tooltip>
                ) : (
                    <Button variant="ghost" size="sm" onClick={() => setRemoving(member)}>
                        <UserMinus /> Remove
                    </Button>
                )),
        }),
    ]);

    return (
        <>
            <Head title={`${team.name} · Members`} />
            <TeamHeader team={team} tab="members" can={NO_LIFECYCLE_ACTIONS} />
            <DataTable
                columns={columns}
                page={members}
                query={table.state}
                onQueryChange={table.update}
                loading={table.loading || processing}
                perPageOptions={perPageOptions}
                getRowId={(member) => member.id}
                searchPlaceholder="Search members..."
                emptyMessage="No members found"
                toolbar={
                    can.update && (
                        <div className="ml-auto">
                            <UserPicker
                                value={null}
                                excludeTeam={team.id}
                                onSelect={(user) =>
                                    router.visit(store(team.id), { ...visitOptions, data: { user_id: user.id } })
                                }
                                trigger={
                                    <Button disabled={processing}>
                                        <Plus /> Add member
                                    </Button>
                                }
                            />
                        </div>
                    )
                }
            />
            {removing && (
                <ConfirmDialog
                    heading={`Remove ${removing.name}?`}
                    text={`${removing.name} loses access to ${team.name}.`}
                    confirmLabel="Yes, remove"
                    destructive
                    processing={processing}
                    onClose={() => setRemoving(null)}
                    onConfirm={() =>
                        router.visit(destroy({ team: team.id, user: removing.id }), {
                            ...visitOptions,
                            onSuccess: () => setRemoving(null),
                        })
                    }
                />
            )}
        </>
    );
}
