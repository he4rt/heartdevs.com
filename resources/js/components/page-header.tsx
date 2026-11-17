import type { ReactNode } from 'react';

export function PageHeader({
    heading,
    description,
    actions,
}: {
    heading: ReactNode;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div className="flex flex-col gap-1">
                <h1 className="text-title font-semibold">{heading}</h1>
                {description && <p className="text-sm text-muted-foreground">{description}</p>}
            </div>
            {actions && <div className="flex items-center gap-2">{actions}</div>}
        </div>
    );
}
