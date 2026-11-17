import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/page-header';
import { Card, CardContent } from '@/components/ui/card';
import { useAccount } from '@app/hooks/use-account';

const PLACEHOLDERS = ['first', 'second', 'third'];

export default function Dashboard() {
    const account = useAccount();

    return (
        <>
            <Head title="Dashboard" />
            <PageHeader heading="Dashboard" description={`Welcome back, ${account?.user.name ?? ''}.`} />
            <div className="grid gap-4 md:grid-cols-3">
                {PLACEHOLDERS.map((placeholder) => (
                    <Card key={placeholder} className="aspect-video border-dashed bg-transparent shadow-none">
                        <CardContent />
                    </Card>
                ))}
            </div>
        </>
    );
}
