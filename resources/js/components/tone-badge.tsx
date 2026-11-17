import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from 'cn';

type Tone = App.Enums.Tone;

const TONE_CLASSES: Record<Tone, string> = {
    neutral: 'bg-secondary text-secondary-foreground',
    primary: 'bg-primary/10 text-primary dark:bg-primary/20',
    info: 'bg-primary/10 text-primary dark:bg-primary/20',
    success: 'bg-success/10 text-success dark:bg-success/20',
    warning: 'bg-warning/10 text-warning dark:bg-warning/20',
    danger: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
};

export function ToneBadge({ tone, children, className }: { tone: Tone; children: ReactNode; className?: string }) {
    return (
        <Badge variant="secondary" className={cn(TONE_CLASSES[tone], className)}>
            {children}
        </Badge>
    );
}
