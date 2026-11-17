import { ToneBadge } from '@/components/tone-badge';

const STATUS_TONES: Record<string, App.Enums.Tone> = {
    pending: 'neutral',
    sent: 'info',
    delivered: 'success',
    bounced: 'danger',
    complained: 'warning',
};

export function MailStatusBadge({ status }: { status: string }) {
    return <ToneBadge tone={STATUS_TONES[status] ?? 'neutral'}>{status}</ToneBadge>;
}
