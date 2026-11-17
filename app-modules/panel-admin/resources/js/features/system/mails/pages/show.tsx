import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, Paperclip } from 'lucide-react';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatDateTime } from '@/lib/format';
import { index } from '@/wayfinder/routes/namespaced/panel-admin/mails';
import { MailStatusBadge } from '../components/mail-status-badge';

type Detail = He4rt.PanelAdmin.Features.System.Mails.Data.MailDetailData;

const KILOBYTE = 1024;

function formatSize(bytes: number): string {
    return bytes < KILOBYTE ? `${bytes} B` : `${(bytes / KILOBYTE).toFixed(1)} KB`;
}

function Header({ label, values }: { label: string; values: string[] }) {
    if (values.length === 0) {
        return null;
    }

    return (
        <div className="contents">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="break-all">{values.join(', ')}</dd>
        </div>
    );
}

export default function MailShow({ mail }: { mail: Detail }) {
    const { row } = mail;
    const defaultTab = mail.hasHtml ? 'html' : 'text';

    return (
        <>
            <Head title={row.subject ?? 'Mail'} />
            <Link
                href={index().url}
                prefetch
                className={buttonVariants({ variant: 'ghost', size: 'sm', className: '-ml-2.5 mb-2' })}
            >
                <ArrowLeft /> Mails
            </Link>
            <PageHeader
                heading={
                    <span className="flex flex-wrap items-center gap-2">
                        {row.subject ?? '(no subject)'}
                        <MailStatusBadge status={row.status} />
                    </span>
                }
                description={`Created ${formatDateTime(row.createdAt)}${mail.sentAt ? ` · Sent ${formatDateTime(mail.sentAt)}` : ''}`}
            />
            <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <Tabs defaultValue={defaultTab}>
                    <TabsList>
                        <TabsTrigger value="html" disabled={!mail.hasHtml}>
                            HTML
                        </TabsTrigger>
                        <TabsTrigger value="text" disabled={!mail.text}>
                            Text
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="html">
                        {mail.hasHtml && (
                            <iframe
                                title="Mail preview"
                                src={mail.previewUrl}
                                sandbox=""
                                className="h-[70vh] w-full rounded-xl border bg-white"
                            />
                        )}
                    </TabsContent>
                    <TabsContent value="text">
                        <pre className="h-[70vh] overflow-auto rounded-xl border bg-muted p-4 text-sm whitespace-pre-wrap">
                            {mail.text}
                        </pre>
                    </TabsContent>
                </Tabs>
                <div className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Headers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-2 text-sm">
                                <Header label="From" values={mail.from} />
                                <Header label="To" values={row.to} />
                                <Header label="Cc" values={mail.cc} />
                                <Header label="Bcc" values={mail.bcc} />
                                <Header label="Reply to" values={mail.replyTo} />
                                <Header label="Mailer" values={[mail.mailer]} />
                                <Header label="Class" values={mail.mailClass ? [mail.mailClass] : []} />
                            </dl>
                        </CardContent>
                    </Card>
                    {mail.attachments.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Attachments</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                {mail.attachments.map((attachment) => (
                                    <a
                                        key={attachment.id}
                                        href={attachment.url}
                                        className="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-muted"
                                    >
                                        <Paperclip className="size-4 text-muted-foreground" />
                                        <span className="min-w-0 flex-1 truncate">{attachment.filename}</span>
                                        <span className="text-xs text-muted-foreground">
                                            {formatSize(attachment.size)}
                                        </span>
                                        <Download className="size-4" />
                                    </a>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                    {mail.events.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Provider events</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm">
                                {mail.events.map((event, position) => (
                                    <div key={position} className="flex items-center justify-between gap-2">
                                        <Badge variant="outline">{event.type}</Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDateTime(event.occurredAt)}
                                        </span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
