import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    stats: He4rt.PanelAdmin.Features.Home.Data.DashboardStatData[];
};

export default function Dashboard({ stats }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <PageHeader heading="Dashboard" description="An overview of this He4rt installation." />
            <dl className="grid gap-4 sm:grid-cols-3">
                {stats.map((stat) => (
                    <Card key={stat.key}>
                        <CardContent className="flex flex-col gap-1">
                            <dt className="text-sm text-muted-foreground">{stat.label}</dt>
                            <dd className="font-heading text-stat font-semibold tabular-nums">{stat.value}</dd>
                        </CardContent>
                    </Card>
                ))}
            </dl>
        </>
    );
}
