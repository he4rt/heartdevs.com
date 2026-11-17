import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ShieldAlert, ShieldCheck, ShieldQuestion } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { index, show } from '@/wayfinder/routes/namespaced/panel-admin/activity';
import { RiskBadge } from '../components/risk-badge';

type Detail = He4rt.PanelAdmin.Features.System.Activity.Data.ActivityDetailData;

const INTEGRITY = {
    valid: {
        Icon: ShieldCheck,
        title: 'Integrity verified',
        text: 'The stored row matches its signature.',
        variant: 'default',
    },
    tampered: {
        Icon: ShieldAlert,
        title: 'Integrity check failed',
        text: 'The row changed after it was written.',
        variant: 'destructive',
    },
    unsigned: {
        Icon: ShieldQuestion,
        title: 'Not signed',
        text: 'This entry was written before signing started.',
        variant: 'default',
    },
} as const;

const CONTEXT_LABELS: Record<string, string> = {
    ip_address: 'IP address',
    user_agent: 'User agent',
    request_id: 'Request ID',
    method: 'Method',
    path: 'Path',
    route: 'Route',
};

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    return typeof value === 'string' ? value : JSON.stringify(value);
}

export default function ActivityShow({ activity }: { activity: Detail }) {
    const { row } = activity;
    const integrity = INTEGRITY[activity.integrity as keyof typeof INTEGRITY] ?? INTEGRITY.unsigned;
    const properties = Object.entries(activity.properties);

    return (
        <>
            <Head title={row.description} />
            <Link
                href={index().url}
                prefetch
                className={buttonVariants({ variant: 'ghost', size: 'sm', className: '-ml-2.5 mb-2' })}
            >
                <ArrowLeft /> Activity log
            </Link>
            <PageHeader
                heading={
                    <span className="flex flex-wrap items-center gap-2">
                        {row.description}
                        <RiskBadge level={row.riskLevel} score={row.riskScore} />
                    </span>
                }
                description={`${formatDateTime(row.createdAt)} · ${row.causerName ?? 'System'}${row.subjectType ? ` · ${row.subjectType} ${row.subjectLabel ?? ''}` : ''}`}
            />
            <div className="flex flex-col gap-6">
                <Alert variant={integrity.variant}>
                    <integrity.Icon />
                    <AlertTitle>{integrity.title}</AlertTitle>
                    <AlertDescription>{integrity.text}</AlertDescription>
                </Alert>
                <Card>
                    <CardHeader>
                        <CardTitle>Changes</CardTitle>
                        <CardDescription>
                            {row.event ? (
                                <Badge variant="outline" className="font-mono">
                                    {row.event}
                                </Badge>
                            ) : (
                                'No event'
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {activity.changes.length === 0 ? (
                            <p className="px-6 pb-6 text-sm text-muted-foreground">No field changes in this entry.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-6">Field</TableHead>
                                        <TableHead>Before</TableHead>
                                        <TableHead className="pr-6">After</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activity.changes.map((change) => (
                                        <TableRow key={change.field}>
                                            <TableCell className="pl-6 font-mono text-xs">{change.field}</TableCell>
                                            <TableCell className="max-w-xs text-destructive break-all whitespace-normal">
                                                {formatValue(change.old)}
                                            </TableCell>
                                            <TableCell className="max-w-xs pr-6 text-success break-all whitespace-normal">
                                                {formatValue(change.new)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Request</CardTitle>
                            <CardDescription>Where the change came from.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                                {Object.entries(CONTEXT_LABELS).map(([key, label]) => (
                                    <div key={key} className="contents">
                                        <dt className="text-muted-foreground">{label}</dt>
                                        <dd className="font-mono text-xs break-all">{activity.context[key] ?? '—'}</dd>
                                    </div>
                                ))}
                            </dl>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Properties</CardTitle>
                            <CardDescription>
                                Log {activity.logName ?? 'default'}. Sensitive values are hidden.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {properties.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No extra properties.</p>
                            ) : (
                                <pre className="overflow-x-auto rounded-lg bg-muted p-3 text-xs">
                                    {JSON.stringify(activity.properties, null, 2)}
                                </pre>
                            )}
                        </CardContent>
                    </Card>
                </div>
                {activity.related.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Same request</CardTitle>
                            <CardDescription>Other activities written by the same request.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2">
                            {activity.related.map((related) => (
                                <Link
                                    key={related.id}
                                    href={show(related.id).url}
                                    prefetch
                                    className="flex items-center justify-between gap-4 rounded-lg border px-3 py-2 text-sm hover:bg-muted"
                                >
                                    <span>{related.description}</span>
                                    <span className="text-xs text-muted-foreground">
                                        {related.subjectType} {related.subjectLabel}
                                    </span>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
