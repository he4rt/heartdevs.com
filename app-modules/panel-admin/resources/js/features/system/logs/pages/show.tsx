import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { ToneBadge } from '@/components/tone-badge';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { show as activity } from '@/wayfinder/routes/namespaced/panel-admin/activity';
import { index, show } from '@/wayfinder/routes/namespaced/panel-admin/logs';
import { show as team } from '@/wayfinder/routes/namespaced/panel-admin/teams';
import { HelpLink } from '@admin/components/help-link';

type Detail = He4rt.PanelAdmin.Features.System.Logs.Data.EventDetailData;

function field(detail: Detail, key: string): string | null {
    return detail.fields.find((candidate) => candidate.key === key)?.value ?? null;
}

function Facts({ items }: { items: [string, string | null][] }) {
    return (
        <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm">
            {items.map(([label, value]) => (
                <div key={label} className="contents">
                    <dt className="text-muted-foreground">{label}</dt>
                    <dd className="font-mono text-xs break-all">{value ?? '—'}</dd>
                </div>
            ))}
        </dl>
    );
}

export default function LogsShow({ event }: { event: Detail }) {
    const { row } = event;
    const duration = row.durationMs === null ? null : `${Math.round(row.durationMs)} ms`;

    return (
        <>
            <Head title={row.title} />
            <Link
                href={index({ query: { day: row.day } }).url}
                prefetch
                className={buttonVariants({ variant: 'ghost', size: 'sm', className: '-ml-2.5 mb-2' })}
            >
                <ArrowLeft /> Logs
            </Link>
            <PageHeader
                heading={
                    <span className="flex min-w-0 flex-wrap items-center gap-2">
                        <span className="font-mono break-all">{row.title}</span>
                        <ToneBadge tone={row.kind.tone}>{row.kind.label}</ToneBadge>
                        <ToneBadge tone={row.outcome.tone}>{row.outcome.label}</ToneBadge>
                        {row.keptBecause && <Badge variant="outline">Kept: {row.keptBecause}</Badge>}
                    </span>
                }
                description={formatDateTime(row.timestamp)}
                actions={<HelpLink path="system/logs" />}
            />
            <div className="flex flex-col gap-6">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Run</CardTitle>
                            <CardDescription>How the work went.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Facts
                                items={[
                                    ['Status', row.status === null ? null : String(row.status)],
                                    ['Duration', duration],
                                    ['Queries', field(event, 'db.queries')],
                                    [
                                        'Memory',
                                        field(event, 'memory_peak_mb') && `${field(event, 'memory_peak_mb')} MB`,
                                    ],
                                    ['Request ID', row.requestId],
                                ]}
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Who</CardTitle>
                            <CardDescription>The user and the team of the work.</CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-2 text-sm">
                            <p>
                                <span className="text-muted-foreground">User </span>
                                {event.userName ?? (row.userId ? 'Deleted user' : 'Nobody signed in')}
                            </p>
                            {event.impersonatorName && (
                                <p>
                                    <span className="text-muted-foreground">Impersonated by </span>
                                    {event.impersonatorName}
                                </p>
                            )}
                            <p>
                                <span className="text-muted-foreground">Team </span>
                                {event.teamId ? (
                                    <Link href={team(event.teamId).url} prefetch className="hover:underline">
                                        {event.teamName ?? 'Deleted team'}
                                    </Link>
                                ) : (
                                    'None'
                                )}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className={event.errorType ? 'border-destructive' : undefined}>
                        <CardHeader>
                            <CardTitle>Error</CardTitle>
                            <CardDescription>
                                {event.errorType ? 'The first reported exception.' : 'No exception.'}
                            </CardDescription>
                        </CardHeader>
                        {event.errorType && (
                            <CardContent className="flex flex-col gap-2 text-sm">
                                <p className="font-mono text-xs break-all">{event.errorType}</p>
                                <p className="break-words">{event.errorMessage}</p>
                                <ol className="flex flex-col gap-1 font-mono text-xs text-muted-foreground">
                                    {event.frames.map((frame, position) => (
                                        <li key={`${position}-${frame}`} className="break-all">
                                            {frame}
                                        </li>
                                    ))}
                                </ol>
                            </CardContent>
                        )}
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Same request</CardTitle>
                        <CardDescription>
                            The other events and the activity log entries with request ID {row.requestId}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 text-sm">
                        {event.related.length === 0 && event.activities.length === 0 ? (
                            <p className="text-muted-foreground">Nothing else shares this request ID.</p>
                        ) : (
                            <ul className="flex flex-wrap gap-2">
                                {event.related.map((related) => (
                                    <li key={related.id}>
                                        <Link
                                            href={show({ day: related.day, offset: related.offset }).url}
                                            prefetch
                                            className={buttonVariants({ variant: 'outline', size: 'sm' })}
                                        >
                                            {related.kind.label}: {related.title} ({related.outcome.label})
                                        </Link>
                                    </li>
                                ))}
                                {event.activities.map((entry) => (
                                    <li key={entry.id}>
                                        <Link
                                            href={activity(entry.id).url}
                                            prefetch
                                            className={buttonVariants({ variant: 'outline', size: 'sm' })}
                                        >
                                            Activity: {entry.event ?? entry.description}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Fields</CardTitle>
                        <CardDescription>Everything the event holds. Sensitive values are hidden.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-6">Field</TableHead>
                                        <TableHead className="pr-6">Value</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {event.fields.map((entry) => (
                                        <TableRow key={entry.key}>
                                            <TableCell className="pl-6 font-mono text-xs">{entry.key}</TableCell>
                                            <TableCell className="max-w-xl pr-6 font-mono text-xs break-all whitespace-normal">
                                                {entry.value}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <details className="rounded-lg border bg-card">
                    <summary className="cursor-pointer px-6 py-4 text-sm font-medium">Raw JSON</summary>
                    <pre className="overflow-x-auto border-t px-6 py-4 font-mono text-xs">{event.json}</pre>
                </details>
            </div>
        </>
    );
}
