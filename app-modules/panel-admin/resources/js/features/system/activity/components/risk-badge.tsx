import { ToneBadge } from '@/components/tone-badge';

const RISK_TONES: Record<string, App.Enums.Tone> = {
    low: 'neutral',
    medium: 'info',
    high: 'warning',
    critical: 'danger',
};

export function RiskBadge({ level, score }: { level: string | null; score: number | null }) {
    if (level === null) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <ToneBadge tone={RISK_TONES[level] ?? 'neutral'}>
            {level} {score !== null && <span className="tabular-nums opacity-70">{score}</span>}
        </ToneBadge>
    );
}
