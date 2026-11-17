import { Link } from '@inertiajs/react';
import { CircleHelp } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { show } from '@/wayfinder/routes/namespaced/panel-admin/docs';

/**
 * Opens the admin docs page about the current screen. `path` is the page path under docs/admin/{locale}.
 */
export function HelpLink({ path }: { path: string }) {
    return (
        <Link href={show(path).url} prefetch className={buttonVariants({ variant: 'ghost' })}>
            <CircleHelp /> Help
        </Link>
    );
}
