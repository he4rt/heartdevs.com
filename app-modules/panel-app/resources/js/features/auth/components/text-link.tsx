import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from 'cn';

export function TextLink({ className, ...props }: ComponentProps<typeof Link>) {
    return (
        <Link
            className={cn(
                'text-foreground underline decoration-muted-foreground/40 underline-offset-4 hover:decoration-current',
                className,
            )}
            {...props}
        />
    );
}
