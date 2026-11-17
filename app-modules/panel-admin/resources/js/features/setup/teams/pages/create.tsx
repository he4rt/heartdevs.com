import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Button, buttonVariants } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { index, store } from '@/wayfinder/routes/namespaced/panel-admin/teams';
import { TeamForm } from '../components/team-form';

type Props = {
    statuses: App.Data.Support.EnumOptionData[];
};

export default function CreateTeam({ statuses }: Props) {
    return (
        <>
            <Head title="New team" />
            <PageHeader heading="New team" description="The owner joins the team as its first member." />
            <TeamForm
                team={null}
                statuses={statuses}
                action={store()}
                footer={(processing) => (
                    <>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Create team
                        </Button>
                        <Link href={index().url} prefetch className={buttonVariants({ variant: 'outline' })}>
                            Cancel
                        </Link>
                    </>
                )}
            />
        </>
    );
}
