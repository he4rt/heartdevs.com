import { Head, Link } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from 'cn';

type DocNode = He4rt.PanelAdmin.Features.System.Docs.Data.DocNodeData;
type DocPage = He4rt.PanelAdmin.Features.System.Docs.Data.DocPageData;

function TreeNodes({ nodes, current, depth = 0 }: { nodes: DocNode[]; current: string; depth?: number }) {
    return (
        <ul className={cn('flex flex-col gap-0.5', depth > 0 && 'mt-0.5 ml-3 border-l pl-2')}>
            {nodes.map((node) => (
                <li key={node.path}>
                    <Link
                        href={node.url}
                        prefetch
                        aria-current={node.path === current ? 'page' : undefined}
                        className={cn(
                            'block rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground',
                            node.path === current && 'bg-muted font-medium text-foreground',
                        )}
                    >
                        {node.title}
                    </Link>
                    {node.children.length > 0 && (
                        <TreeNodes nodes={node.children} current={current} depth={depth + 1} />
                    )}
                </li>
            ))}
        </ul>
    );
}

export default function DocsShow({ page, tree }: { page: DocPage; tree: DocNode[] }) {
    return (
        <>
            <Head title={`${page.title} · Docs`} />
            <div className="grid gap-8 lg:grid-cols-[14rem_1fr]">
                <nav aria-label="Documentation" className="lg:sticky lg:top-6 lg:self-start">
                    <TreeNodes nodes={tree} current={page.path} />
                </nav>
                <article className="flex min-w-0 flex-col gap-6">
                    {page.isFallback && (
                        <Alert>
                            <Languages />
                            <AlertDescription>
                                This page is not translated yet, so it shows in English.
                            </AlertDescription>
                        </Alert>
                    )}
                    <div
                        className="prose prose-sm max-w-none dark:prose-invert prose-headings:tracking-tight prose-a:text-primary"
                        dangerouslySetInnerHTML={{ __html: page.html }}
                    />
                    {page.children.length > 0 && (
                        <div className="grid gap-3 sm:grid-cols-2">
                            {page.children.map((child) => (
                                <Link key={child.path} href={child.url} prefetch>
                                    <Card className="h-full transition-colors hover:bg-muted/50">
                                        <CardHeader>
                                            <CardTitle>{child.title}</CardTitle>
                                            <CardDescription>{child.path}</CardDescription>
                                        </CardHeader>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </article>
            </div>
        </>
    );
}
