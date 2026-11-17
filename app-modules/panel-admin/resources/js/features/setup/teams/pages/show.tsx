import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/wayfinder/routes/namespaced/panel-admin/teams';
import { TeamForm } from '../components/team-form';
import { TeamHeader, type TeamAbilities } from '../components/team-header';

type Props = {
    team: He4rt.PanelAdmin.Features.Setup.Teams.Data.TeamDetailData;
    statuses: App.Data.Support.EnumOptionData[];
    can: TeamAbilities & { update: boolean };
};

export default function ShowTeam({ team, statuses, can }: Props) {
    return (
        <>
            <Head title={team.name} />
            <TeamHeader team={team} tab="details" can={can} />
            <TeamForm
                key={team.id}
                team={team}
                statuses={statuses}
                action={update(team.id)}
                readOnly={!can.update}
                footer={(processing) => (
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner />}
                        Save changes
                    </Button>
                )}
            />
        </>
    );
}
